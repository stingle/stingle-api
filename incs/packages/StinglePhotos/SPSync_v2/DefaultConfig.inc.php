<?php

$defaultConfig = [
	'AuxConfig' => [
		'filesPath' => 'files/',
		'thumbsPath' => 'thumbs/',
		'defaultUrlExpiration' => '+12 hours'
	],
	'Objects' => [
		'SPSyncManager' => 'spsync'
	],
    'Tables' => [
        'sp_album_files' => 1,
        'sp_albums' => 1,
        'sp_contacts' => 1,
        'sp_deletes' => 1,
        'sp_files' => 1,
        'sp_trash' => 1
    ]
];
