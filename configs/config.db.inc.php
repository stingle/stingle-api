<?php
///////////  Database config  ///////////

$CONFIG['Db']['Db']['AuxConfig']['instances'] = [
	'default' => [
		'endpoints' => [
			[
				'type' => 'rw',
				'host' => 'localhost',
				'user' => 'stingle',
				'password' => '',
				'name' => 'stingle',
				'isPersistent' => false,
				'encoding' => 'utf8mb4'
			]
		],
		'readsFromRWEndpoint' => false
	]
];

///////////  Memcache config  ///////////

$CONFIG['Db']['Memcache']['AuxConfig'] = [
    'enabled' => true,
    'host' => 'memcached',
    'port' => "11211",
    'keyPrefix' => 'st'
];
