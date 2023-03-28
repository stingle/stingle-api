<?php

isLogined();

Reg::get('packageMgr')->usePlugin('Users', 'UserValidation');
Reg::get('packageMgr')->usePlugin('Security', 'OneTimeCodes');

try {
    $paramsStr       = (isset($_POST['params'])) ? $_POST['params'] : null;
    $keyBundleObj = Reg::get('spkeys')->getKeyBundleByUserId(Reg::get('usr')->id);
    $params = SPKeyManager::getParamsFromEncMessage($paramsStr, $keyBundleObj);
    
    if(empty($params)){
        throw new RuntimeException("Invalid params!");
    }
    
    $password       = $params->newPassword;
    $salt           = $params->newSalt;
    $keyBundle		= $params->keyBundle;
    
    Reg::get('userValidation')->checkPassword($password);
    Reg::get('userValidation')->checkSalt($salt);
    
    if (Reg::get('userValidation')->hasError()) {
        foreach (Reg::get('userValidation')->getErrors() as $err) {
            Reg::get('error')->add($err);
        }
        
        Reg::get('ao')->setStatusNotOk();
    }
    else {
        if(!Reg::get('userMgr')->setUserPassword(Reg::get('usr'), $password)){
            throw new RuntimeException("Unable to set password!");
        }
        Reg::get('usr')->props->pwsalt = $salt;
        
        Reg::get('userMgr')->updateUser(Reg::get('usr'));
        
        Reg::get('spkeys')->updateKeyBundle(Reg::get('usr')->id, $keyBundle);
        
        Reg::get('userSess')->revokeAllSessions(Reg::get('usr')->id);
        
        Reg::get('ao')->set('token', Reg::get('userSess')->addSession(Reg::get('usr')->id));
    }
}
catch (Exception $e){
    Reg::get('error')->add(C('Unexpected error!'));
    Reg::get('ao')->setStatusNotOk();
}