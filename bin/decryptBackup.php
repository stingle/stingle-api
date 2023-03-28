#!/usr/bin/php
<?php
require_once 'vendor/alexamiryan/stingle/packages/Crypto/AES256/Managers/AES256File.class.php';
if(empty($argv) || count($argv) != 2){
    echo "Usage: decryptBackup.php pathToFile\n\n";
    exit;
}

$filePath = $argv[1];

echo "Please enter keypair to decrypt: ";
$handle = fopen ("php://stdin","r");
$key = fgets($handle);
fclose($handle);


$pathParts = pathinfo($filePath);
$decFilename = basename($filePath, ".enc");
$encKeyFile = $pathParts['dirname'] . "/" . $decFilename . '.key';
$encKey = file_get_contents ($encKeyFile);
try {
    $decKey = sodium_crypto_box_seal_open($encKey, base64_decode($key));
}
catch (Exception $e){}

if(empty($decKey)){
    echo "Key decrypt failed!\n";
    exit;
}
$result = AES256File::decryptFile($filePath, $decKey, $pathParts['dirname'] . "/" . $decFilename);
if($result === false){
    echo "File decrypt failed!\n";
    exit;
}

echo "Success!\n";

echo "Import backups using following commands:
mysql -hmysql-server -uuser -p stingle_api < /PATH_TO_BACKUPS/stingle_mysql.sql
\n";