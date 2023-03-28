<?php
function setSitePath(){
	define('SITE_PATH',ConfigManager::getConfig("RewriteURL")->AuxConfig->sitePath);
}

function localNoDebugExceptionHandler($params){
	extract($params);
	
	$doNotRecordOnExceptions = array(	
			"UserAuthFailedException",
			"IPBlockedException", 
			"SecurityException",
			"RequestLimiterBlockedException",
			"FormKeySecurityException",
			"NoSuchHostException",
			"FileNotFoundException"
	);
	
	if(!in_array(get_class($e), $doNotRecordOnExceptions)){
		HookManager::callHook("Exception", $e);
	}
}

function recordException($e){
	if(Reg::isRegistered('sql') and Reg::get('packageMgr')->isPluginLoaded('Logger', 'DBLogger')){
		DBLogger::logCustom("Exception", format_exception($e));
	}
	if(ConfigManager::getGlobalConfig()->Debug->send_email_on_exception and Reg::isRegistered('mail')){
		Reg::get('mail')->exception("Exception", format_exception($e, true));
	}
    
    if(ConfigManager::getGlobalConfig()->Debug->send_keybase_on_exception and Reg::isRegistered('keybase')) {
        Reg::get("keybase")->send(format_exception($e), 'exceptions');
    }
}

function localExceptionHandler($params) {
    if (!empty($params) && !empty($params['e'])) {
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        header('Content-type: application/json');
        echo json_encode(['status' => 'nok']);
    }
}

