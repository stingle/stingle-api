<?php

function isLogined($makeOutput = true, $doLogoutOnFailure = true) {
    if (!isAuthorized()) {
        $message =
            "Get:\n" . print_r($_GET, true) . "\n\n" .
            "Post:\n" . print_r($_POST, true) . "\n\n" .
            "Cookie:\n" . print_r($_COOKIE, true) . "\n\n" .
            "Files:\n" . print_r($_FILES, true) . "\n\n";
        if (isset($_SESSION)) {
            $message .= "Session:\n" . print_r($_SESSION, true) . "\n\n";
        }
        $message .= "Server:\n". print_r($_SERVER,true);
        DBLogger::logCustom("Logout", $message);
        
        if ($makeOutput) {
            if($doLogoutOnFailure) {
                Reg::get('error')->add(C('Not authorized! Please login.'));
                Reg::get('ao')->set('logout', 1);
            }
            Reg::get('ao')->setStatusNotOk();
            Reg::get('ao')->output();
        }
        exit;
    }
    return true;
}

function getApiRequestSecureParams($makeOutput = true, $isPost = true){
    isLogined($makeOutput);
    if($isPost) {
        $paramsStr = (isset($_POST['params'])) ? $_POST['params'] : null;
    }
    else{
        $paramsStr = (isset($_GET['params'])) ? $_GET['params'] : null;
    }
    $keyBundleObj = Reg::get('spkeys')->getKeyBundleByUserId(Reg::get('usr')->id);
    return SPKeyManager::getParamsFromEncMessage($paramsStr, $keyBundleObj);
}

function logInDbAndKeybase($name, $keybaseChannel, $msg){
    DBLogger::logCustom($name, $msg);
    Reg::get("keybase")->send($msg, $keybaseChannel);
}

function healthCheck(){
    try {
        $pager = new MysqlPager(1);
        $users = Reg::get('userMgr')->getUsersList(null, $pager);
        if(!empty($users)) {
            Reg::get('spsync')->getFileFromDb('sdfsdfdsf', $users[0]);
        }
        return true;
    }
    catch (Exception $e){
        Reg::get("keybase")->send("ATTENTION! Stingle API server is down!\n\n" . $e->getMessage(), 'exceptions');
    }
    return false;
}