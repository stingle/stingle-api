<?php
ob_end_flush();
$mysqlConfig = ConfigManager::getConfig("Db", "Db")->AuxConfig->instances->default->endpoints->toArray()[0];

$backupFilename = readline("Backup filename: ");
$keypair = readline("Keypair: ");

$encFilename = $backupFilename . '.tar.gz.enc';
$keyFilename = $backupFilename . '.tar.gz.key';

$localPath = 'backups/restore-' . $backupFilename;
if(!file_exists($localPath)) {
    mkdir($localPath, 0755, true);
}

$localEncFile = $localPath . '/' . $encFilename;
$localDecFile = $localPath . '/' . $backupFilename . '.tar.gz';
$localKeyFile = $localPath . '/' . $keyFilename;

try {
    if(!file_exists($localEncFile)) {
        echo "Downloading $encFilename...\n";
        S3Transport::download($encFilename, $localEncFile, 'backup');
    }
    if(!file_exists($localKeyFile)) {
        echo "Downloading $keyFilename...\n";
        S3Transport::download($keyFilename, $localKeyFile, 'backup');
    }
}
catch (Exception $e){
    echo format_exception($e);
}

echo "Decrypting $localEncFile...\n";

$encKey = file_get_contents ($localKeyFile);
try {
    $decKey = sodium_crypto_box_seal_open($encKey, base64_decode($keypair));
}
catch (Exception $e){}

if(empty($decKey)){
    echo "Key decrypt failed!\n";
    exit;
}
$result = AES256File::decryptFile($localEncFile, $decKey, $localDecFile);
if($result === false){
    echo "File decrypt failed!\n";
    exit;
}

echo "Extracting $localDecFile...\n";

$extractOutput = array();
$extractReturn = null;
exec("tar -xvzf $localDecFile -C $localPath", $extractOutput, $extractReturn);
if ($extractReturn != 0) {
    throw new RuntimeException("Extraction failed!");
}

$dumpFile = $localPath . "/stingle_mysql.sql";

$continueYes = readline("This operation WILL DELETE current database and replace it with the backup!!!
Are you sure you want to continue? (Y/n): ");
if (strtolower(trim($continueYes)) == 'y') {
    $sql = MySqlDbManager::getQueryObject();
    echo "Dropping database {$mysqlConfig->name}...\n";
    $sql->exec("DROP DATABASE {$mysqlConfig->name}");
    echo "Creating database {$mysqlConfig->name}...\n";
    $sql->exec("CREATE DATABASE {$mysqlConfig->name}");
    
    $mysqlCommand = "mysql -h{$mysqlConfig->host} -u{$mysqlConfig->user} -p\"{$mysqlConfig->password}\" {$mysqlConfig->name} < $dumpFile";
    
    echo "Importing $dumpFile to database {$mysqlConfig->name}...\n";
    $mysqlOutput = array();
    $mysqlReturn = null;
    exec($mysqlCommand, $mysqlOutput, $mysqlReturn);
    if ($mysqlReturn != 0) {
        throw new RuntimeException("Mysql restore failed!");
    }
    echo "Mysql successfully restored! Please clear all the caches\n";
}

exec("rm -f $localDecFile");
exec("rm -f $dumpFile");

$delTmp = readline("Do you want to delete downloaded backup files? (Y/n): ");
if (strtolower(trim($delTmp)) == 'y') {
    exec("rm -rf $localPath");
}

exit;