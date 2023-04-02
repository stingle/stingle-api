<?php

// Core config
$CONFIG['Stingle']['errorReporting'] = E_ALL;
$CONFIG['Stingle']['autostartSession'] = false;
$CONFIG['Stingle']['siteName'] = 'StinglePhotos';
$CONFIG['Stingle']['suppressRemoteAddrInExceptions'] = true;

// Users
$CONFIG['Users']['Users']['AuxConfig'] = [
    'secondFactorOrder' => ['googleAuth', 'yubiKey'],
    'siteSalt' => '***REMOVED***',
    'pbdkf2IterationCount' => 2048,
    'useSessions' => false,
    'saveLastLoginDateIP' => false,
    'useCookies' => false,
    'userPropertiesMap' => [
        'pwsalt' => 'pwsalt',
        'baseQuota' => 'base_quota',
        'spaceUsed' => 'space_used',
        'spaceQuota' => 'space_quota'
    ]
];

// User sessions
$CONFIG['Users']['UserSessions']['AuxConfig'] = [
    'registerUserObjectFromToken' => true,
    'tokenPlace' => 'post',
    'tokenName' => 'token'
];

// Hooks config
$CONFIG['Hooks'] = array(
	'BeforePackagesLoad' => array(
		'setSitePath'
	),
	'BeforeRequestParser' => array(
		
	),
	'BeforeController' => array(
		
	),
	'BeforeOutput' => array(
		
	),
	'NoDebugExceptionHandler' => array(
		'localNoDebugExceptionHandler'
	),
	'ExceptionHandler' => array(
		'localExceptionHandler'
	),
	'ErrorHandler' => array(
		'localExceptionHandler'
	),
	'Exception' => array(
		'recordException'
	)
);

// Don't auto create hosts
$CONFIG['Host']['Host']['AuxConfig']['autoCreateHost'] = false;

// API versioning config
$CONFIG['SiteNavigation']['APIVersioning']['AuxConfig']['currentApiVersion'] = 2;
$CONFIG['SiteNavigation']['APIVersioning']['AuxConfig']['replaceWithVersionIfAbsent'] = 2;

// Disable all cookies
$CONFIG['Language']['Language']['AuxConfig'] = [
    'useSession' => false,
    'useCookies' => false
];
$CONFIG['Logger']['DBLogger']['AuxConfig']['saveIPInCustomLog'] = false;
$CONFIG['Logger']['DBLogger']['AuxConfig']['isUsingSessions'] = false;