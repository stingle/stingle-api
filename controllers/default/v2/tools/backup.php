<?php
ob_end_flush();

$BACKUP_PATH = 'backups/';

$folderName = date("Y-m-d_H-i-s");
$backupPath = $BACKUP_PATH . $folderName;

try {
    echo "Starting backup\n";
    
    if (!mkdir($BACKUP_PATH . $folderName)) {
        throw new RuntimeException("Failed to create backup dir!");
    }
    
    $backupPath .= '/';
    
    $mysqlConfig = ConfigManager::getConfig("Db", "Db")->AuxConfig->instances->default->endpoints->toArray()[0];
    
    $mysqlDumpCommand = "mysqldump -h{$mysqlConfig->host} -u{$mysqlConfig->user} -p\"{$mysqlConfig->password}\" {$mysqlConfig->name} > {$backupPath}stingle_mysql.sql";
    
    $mysqlDumpOutput = array();
    $mysqlDumpReturn = null;
    exec($mysqlDumpCommand, $mysqlDumpOutput, $mysqlDumpReturn);
    
    if ($mysqlDumpReturn != 0) {
        throw new RuntimeException("Mysql backup failed!");
    }
    echo "Mysql successfully dumped!\n";
    
    if(!empty($_GET['onlyDump'])){
        exit;
    }
    
    $archivePath = "$BACKUP_PATH/$folderName.tar.gz";
    
    $compressOutput = array();
    $compressReturn = null;
    exec("tar -czf $archivePath -C $backupPath .", $compressOutput, $compressReturn);
    
    if ($compressReturn != 0) {
        throw new RuntimeException("Compression failed!");
    }
    echo "Successfully compressed!\n";
    exec("rm -rf $backupPath");
    
    $BACKUP_PUB_KEY = ConfigManager::getGlobalConfig()->Backup->PublicKey;
    
    $keypair_public = base64_decode($BACKUP_PUB_KEY);
    
    $encArchivePath = $archivePath . '.enc';
    $encKeyPath = $archivePath . '.key';
    $key = AES256File::encryptFile($archivePath, $encArchivePath);
    
    if($key === false){
        throw new RuntimeException("File encryption failed!");
    }
    
    $encryptedKey = sodium_crypto_box_seal($key, $keypair_public);
    
    file_put_contents($encKeyPath, $encryptedKey);
    echo "Successfully encrypted!\n";
    
    exec("rm -rf $archivePath");
    echo "Successfully deleted plain tar.gz!\n";
    
    if (empty($_GET['noupload'])) {
        $uploadResult = S3Transport::upload($encArchivePath, basename($encArchivePath), 'private', 'backup');
        $uploadResult = S3Transport::upload($encKeyPath, basename($encKeyPath), 'private', 'backup');
        
        if (!$uploadResult) {
            throw new RuntimeException("Upload failed!");
        }
        
        echo "Successfully uploaded!\n";
        echo "Filename for restoration: $folderName\n";
        
    }
    
    $LOCAL_BACKUPS_LIMIT = ConfigManager::getGlobalConfig()->Backup->Limit;
    $files = glob( "./$BACKUP_PATH*.enc" );
    deleteFiles($files, $LOCAL_BACKUPS_LIMIT);
    $keys = glob( "./$BACKUP_PATH*.key" );
    deleteFiles($keys, $LOCAL_BACKUPS_LIMIT);
    
}
catch (Exception $e){
    $error = "Backup failed!\n\n{$e->getMessage()} in {$e->getFile()} on line {$e->getLine()}\n\n";
    echo $error;
    exec("rm -rf $backupPath");
    Reg::get("keybase")->send($error, "exceptions");
}

function deleteFiles($files, $limit){
    if(count($files) > $limit){
        array_multisort(
            array_map( 'filemtime', $files ),
            SORT_NUMERIC,
            SORT_ASC,
            $files
        );
        exec("rm -rf ". $files[0]);
    }
}
exit;