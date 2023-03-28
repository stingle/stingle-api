#!/usr/bin/php
<?php
require_once "vendor/alexamiryan/stingle/packages/Crypto/Crypto/Helpers/helpers.inc.php";
$CONFIG_FILE = 'configsSite/config.override.inc.php';
$shortopts  = "h";

$longopts  = array(
    "full",
    "mysqlPass::",
    "mysql",
    "systemKeys",
    "storage",
    "backup",
    "hostname",
    "help"
);
$options = getopt($shortopts, $longopts);

// Help
if(empty($options) || isset($options['h']) || isset($options['help'])){
    echo "
Setup script of Stingle API server
Usage:
--full          Run full setup
--mysqlPass     MySQL password
--mysql         MySQL setup
--systemKeys    Generate system keys
--storage       S3 storage configuration
--backup        Backup configuration
--hostname      Set a hostname
-h --help       Display this help message
";
    exit;
}

$isFull = isset($options['full']);
if(file_exists($CONFIG_FILE)){
    $config = file_get_contents($CONFIG_FILE);
}
else {
    $config = "<?php
";
}


// MySQL
if($isFull || isset($options['mysql'])) {
    if (empty($options['mysqlPass'])) {
        echo "Mysql password is empty. Aborting!\n";
        exit;
    }
    removeBlock($config, "MYSQL");
    $config .= "
## MYSQL
\$CONFIG['Db']['Db']['AuxConfig']['instances'] = [
    'default' => [
        'endpoints' => [
            [
                'type' => 'rw',
                'host' => 'mysql-server',
                'user' => 'user',
                'password' => '{$options['mysqlPass']}',
                'name' => 'stingle_api',
                'isPersistent' => false,
                'encoding' => 'utf8mb4'
            ]
        ],
        'readsFromRWEndpoint' => false
    ]
];
\$CONFIG['Db']['Memcache']['AuxConfig']['host'] = 'memcached';
## /MYSQL
";
}


// System Keys
if($isFull || isset($options['systemKeys'])) {
    $aesKey = generateRandomString(40, [RANDOM_STRING_LOWERCASE, RANDOM_STRING_UPPERCASE, RANDOM_STRING_DIGITS, RANDOM_STRING_SYMBOLS]);
    $aesIV = generateRandomString(32, [RANDOM_STRING_LOWERCASE, RANDOM_STRING_UPPERCASE, RANDOM_STRING_DIGITS, RANDOM_STRING_SYMBOLS]);
    $aesSalt = generateRandomString(32, [RANDOM_STRING_LOWERCASE, RANDOM_STRING_UPPERCASE, RANDOM_STRING_DIGITS, RANDOM_STRING_SYMBOLS]);
    $siteSalt = generateRandomString(64, [RANDOM_STRING_LOWERCASE, RANDOM_STRING_UPPERCASE, RANDOM_STRING_DIGITS, RANDOM_STRING_SYMBOLS]);
    $SPEncKey = base64_encode(sodium_crypto_secretbox_keygen());
    
    removeBlock($config, "SYSTEMKEYS");
    $config .= "
## SYSTEMKEYS
\$CONFIG['Debug']['enabled'] = false;
\$CONFIG['Debug']['send_keybase_on_exception'] = false;

\$CONFIG['Crypto']['AES256']['AuxConfig']['key'] = '$aesKey';
\$CONFIG['Crypto']['AES256']['AuxConfig']['iv'] = '$aesIV';
\$CONFIG['Crypto']['AES256']['AuxConfig']['salt'] = '$aesSalt';
\$CONFIG['Users']['Users']['AuxConfig']['siteSalt'] = '$siteSalt';
\$CONFIG['StinglePhotos']['SPKeys']['AuxConfig']['encryptionKey'] = '$SPEncKey';
## /SYSTEMKEYS

";
}

// Storage
if($isFull || isset($options['storage'])) {
    $s3Endpoint = readline("Please enter your S3 endpoint URL (https://s3.us-west-1.wasabisys.com): ");
    if (empty($s3Endpoint)) {
        $s3Endpoint = 'https://s3.us-west-1.wasabisys.com';
    }
    $s3BaseUrl = readline("Please enter your S3 endpoint BASE URL for public links (s3.wasabisys.com): ");
    if (empty($s3BaseUrl)) {
        $s3BaseUrl = 's3.wasabisys.com';
    }
    while (empty($s3Key)) $s3Key = prompt_silent("Please enter your S3 endpoint KEY: ");
    while (empty($s3Secret)) $s3Secret = prompt_silent("Please enter your S3 endpoint SECRET: ");
    while (empty($s3Bucket)) $s3Bucket = readline("Please enter your S3 endpoint bucket name: ");
    
    $s3Region = readline("Please enter your S3 endpoint region (us-east-1): ");
    if (empty($s3Region)) {
        $s3Region = 'us-east-1';
    }
    $s3CFURL = readline("Please enter your S3 endpoint CloudFront URL (leave empty to disable): ");
    
    removeBlock($config, "STORAGE");
    $config .= "
## STORAGE
\$CONFIG['File']['S3Transport']['AuxConfig']['configs']['default'] = [
	'credentials' => array(
		'key' => '$s3Key',
		'secret' => '$s3Secret',
	),
	'region' => '$s3Region',
	'regionForLink' => '',
	'endpoint' => '$s3Endpoint',
	'baseUrl' => '$s3BaseUrl',
	'bucket' => '$s3Bucket',
	'cloudFrontEnabled' => " . (!empty($s3CFURL) ? 'true' : 'false') . ",
	'cloudFrontUrl' => '$s3CFURL'
];
## /STORAGE

";
}

// Backup
if($isFull || isset($options['backup'])) {
    $createTLS = readline("Do you want to enable backup? (Y/n): ");
    if (strtolower(trim($createTLS)) == 'y') {
        $s3BackupEndpoint = readline("Please enter your backup S3 endpoint URL (https://s3.us-west-1.wasabisys.com): ");
        if (empty($s3BackupEndpoint)) {
            $s3BackupEndpoint = 'https://s3.us-west-1.wasabisys.com';
        }
        
        while (empty($s3BackupKey)) $s3BackupKey = prompt_silent("Please enter your backup S3 endpoint KEY: ");
        while (empty($s3BackupSecret)) $s3BackupSecret = prompt_silent("Please enter your backup S3 endpoint SECRET: ");
        while (empty($s3BackupBucket)) $s3BackupBucket = readline("Please enter your backup S3 endpoint bucket name: ");
        
        $s3BackupRegion = readline("Please enter your backup S3 endpoint region (us-east-1): ");
        if (empty($s3BackupRegion)) {
            $s3BackupRegion = 'us-east-1';
        }
        
        $keypair = sodium_crypto_box_keypair();
        $privateKey = sodium_crypto_box_secretkey($keypair);
        $publicKey = sodium_crypto_box_publickey($keypair);
        
        $keypairB64 = base64_encode($keypair);
        $privateKeyB64 = base64_encode($privateKey);
        $publicKeyB64 = base64_encode($publicKey);
        
        echo "
########## IMPORTANT! ##########
Backups will be encrypted with Public Key and server should not have any access to corresponding Private Key.
In case server or it's backups are compromised there should be now way of decrypting backups.
Please save following encoded Key Pair in a safe place!
It will not be shown AGAIN!!!

Key Pair:           $keypairB64
Private Key:        $privateKeyB64
Public Key:         $publicKeyB64

You can decrypt backup files by running 'bin/decryptBackup.php'
################################
";
    
        removeBlock($config, "BACKUP");
        $config .= "
## BACKUP
\$CONFIG['File']['S3Transport']['AuxConfig']['configs']['backup'] = [
	'credentials' => array(
		'key' => '$s3BackupKey',
		'secret' => '$s3BackupSecret',
	),
	'region' => '$s3BackupRegion',
	'regionForLink' => '',
	'endpoint' => '$s3BackupEndpoint',
	'baseUrl' => '',
	'bucket' => '$s3BackupBucket',
	'cloudFrontEnabled' => 'false',
	'cloudFrontUrl' => ''
];
\$CONFIG['Backup']['PublicKey'] = '$publicKeyB64';
## /BACKUP

";
        if(!existsInCrontab("cgi.php module=v2 page=tools subpage=backup")){
            exec('(crontab -l ; echo "0 0,12 * * *    cd /var/www/html/ && ./cgi.php module=v2 page=tools subpage=backup") | crontab');
        }
    }
}

// Hostname
if($isFull || isset($options['hostname'])) {
    $hostname = readline("Please enter hostname of this instance: ");
    
    removeBlock($config, "HOSTNAME");
    $config .= "
## HOSTNAME
\$CONFIG['Host']['Host']['AuxConfig']['cgiHost'] = '$hostname';
## /HOSTNAME
";
    // Write hostname into Apache virtual host
    $apacheConfPath = '/etc/apache2/sites-enabled/000-default.conf';
    $apacheLeConfPath = '/etc/apache2/sites-enabled/000-default-le-ssl.conf';
    if(file_exists($apacheLeConfPath)) {
        unlink($apacheLeConfPath);
    }
    
    $apacheConf = file_get_contents($apacheConfPath);
    $apacheConf = preg_replace('/ServerName .+$/m', '###### SERVER NAME PLACEHOLDER ######', $apacheConf);
    $apacheConf = str_replace('###### SERVER NAME PLACEHOLDER ######', "ServerName $hostname", $apacheConf);
    
    $createTLS = readline("Do you want to create HTTPS certificate for this instance? (Y/n): ");
    if (strtolower(trim($createTLS)) == 'y') {
        $apacheConf = preg_replace('/<VirtualHost \*:443>.*<\/VirtualHost>/sm', '', $apacheConf);
        file_put_contents($apacheConfPath, $apacheConf);
        exec('service apache2 reload');
        
        $certbotEmail = readline("Please enter your email to receive notifications from letsencrypt: ");
        $exitCode = 0;
        $output = null;
        exec("certbot --apache -n --agree-tos --email $certbotEmail -d $hostname", $output, $exitCode);
        if ($exitCode == 0) {
            if(!existsInCrontab("certbot renew")) {
                exec('(crontab -l ; echo "0 0 * * * certbot renew") | crontab');
            }
        }
    } else {
        file_put_contents($apacheConfPath, $apacheConf);
        exec('service apache2 reload');
    }
}

if (!file_put_contents($CONFIG_FILE, $config)) {
    echo "\nERROR! Failed to write config file!\n\n";
    exit;
}
exec('chmod -R 777 cache/');

echo "\nSETUP COMPLETE!\n\n";

function prompt_silent($prompt = "Enter Password:") {
    if (preg_match('/^win/i', PHP_OS)) {
        $vbscript = sys_get_temp_dir() . 'prompt_password.vbs';
        file_put_contents(
            $vbscript, 'wscript.echo(InputBox("'
            . addslashes($prompt)
            . '", "", "password here"))');
        $command = "cscript //nologo " . escapeshellarg($vbscript);
        $password = rtrim(shell_exec($command));
        unlink($vbscript);
        return $password;
    } else {
        $command = "/usr/bin/env bash -c 'echo OK'";
        if (rtrim(shell_exec($command)) !== 'OK') {
            trigger_error("Can't invoke bash");
            return;
        }
        $command = "/usr/bin/env bash -c 'read -s -p \""
            . addslashes($prompt)
            . "\" mypassword && echo \$mypassword'";
        $password = rtrim(shell_exec($command));
        echo "\n";
        return $password;
    }
}

function removeBlock(&$config, $blockName){
    $config = preg_replace("/## $blockName.+## \/$blockName/s", '', $config);
}

function existsInCrontab($string){
    $crontab = shell_exec("crontab -l");
    if(preg_match("/$string/m", $crontab)) {
        return true;
    }
    return false;
}