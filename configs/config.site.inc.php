<?php
$CONFIG['site']['siteName'] = 'StinglePhotos';
$CONFIG['Host']['Host']['AuxConfig']['cgiHost'] = "api.stingle.org";


$CONFIG['Crypto']['AES256']['AuxConfig']['key'] = '';

$CONFIG['Backup']['PublicKey'] = '';
$CONFIG['Backup']['Limit'] = 5;


$CONFIG['File']['S3Transport']['AuxConfig']['configs']['default'] = [
	'credentials' => array(
		'key' => '***REMOVED***',
		'secret' => '***REMOVED***',
	),
	'region' => 'us-east-1',
	'regionForLink' => '',
	'endpoint' => 'https://s3.us-west-1.wasabisys.com',
	'baseUrl' => 's3.wasabisys.com',
	'bucket' => 'stingle-photos',
	'cloudFrontEnabled' => false,
	'cloudFrontUrl' => 'https://xxxxxxx.cloudfront.net/'
];

$CONFIG['File']['S3Transport']['AuxConfig']['configs']['backup'] = [
    'credentials' => array(
        'key' => '***REMOVED***',
        'secret' => '***REMOVED***',
    ),
    'region' => 'eu-central-1',
    'regionForLink' => '',
    'endpoint' => 'https://s3.eu-central-1.wasabisys.com',
    'baseUrl' => 's3.wasabisys.com',
    'bucket' => 'stingle-backup',
    'cloudFrontEnabled' => false,
    'cloudFrontUrl' => 'https://xxxxxxx.cloudfront.net/'
];

$CONFIG['File']['FileUploader']['AuxConfig'] = [
	'storageProvider' => 's3',
	'S3Config' => [
		'configName' => 'default',
		'path' => 'uploads/',
		'acl' => 'private'
	]
];


$CONFIG['Security']['RequestLimiter']['AuxConfig']['limits'] = [
	'gen' => 500,
	'preLogin' => 10,
	'login' => 5,
	'reg' => 4,
    'contact' => 10,
    'share' => 5
];

// Routes exempted from the general rate limiter (matched as "<section>/<action>").
// File download / signed-URL endpoints are excluded so aggressive thumbnail
// fetching during sync doesn't trip the 'gen' flood limit.
$CONFIG['Security']['RequestLimiter']['AuxConfig']['excludedPaths'] = [
    'sync/download',
    'sync/downloadRedir',
    'sync/getUrl',
    'sync/getDownloadUrls',
];

$CONFIG['Users']['SiteUser']['AuxConfig']['defaultQuota'] = 1024; // In MB


// Mail: send all framework-generated email through the Resend (https://resend.com) API.
$CONFIG['Mail']['Mail']['AuxConfig']['mailParams']['default']['fromMail'] = 'no-reply@stingle.org';
$CONFIG['Mail']['Mail']['AuxConfig']['mailParams']['default']['fromName'] = 'Stingle Photos';
$CONFIG['Mail']['Mail']['AuxConfig']['mailParams']['default']['transport'] = 'ResendTransport';
// Secrets (real values go in configsSite/config.override.inc.php, which is gitignored):
//   $CONFIG['Mail']['Resend']['AuxConfig']['apiKey']        = 're_...';
//   $CONFIG['Mail']['Resend']['AuxConfig']['webhookSecret'] = 'whsec_...'; // for bounce webhook
$CONFIG['Mail']['Resend']['AuxConfig']['apiKey'] = '';
$CONFIG['Mail']['Resend']['AuxConfig']['webhookSecret'] = '';

