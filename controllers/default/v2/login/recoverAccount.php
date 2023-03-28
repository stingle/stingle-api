<?php

$email = (isset($_POST['email'])) ? $_POST['email'] : null;
$params = (isset($_POST['params'])) ? $_POST['params'] : null;

recordRequest('recoverAccount');

if(!Reg::get('userMgr')->isLoginExists($email)){
    Reg::get('error')->add(C('User with specified email doesn\'t exist'));
    Reg::get('ao')->setStatusNotOk();
}

if(empty($params)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    try {
        $usr = Reg::get('userMgr')->getUserByLogin($email);
        $keyBundle = Reg::get('spkeys')->getKeyBundleByUserId($usr->id);
        
        $post = SPKeyManager::getParamsFromEncMessage($params, $keyBundle);
        if(!empty($post) && !empty($post->keyBundle) && !empty($post->newPassword) && !empty($post->newSalt)){
            Reg::get('spkeys')->updateKeyBundle($usr->id, $post->keyBundle);
            
            if(!Reg::get('userMgr')->setUserPassword($usr, $post->newPassword)){
                throw new RuntimeException("Unable to set password!");
            }
            $usr->props->pwsalt = $post->newSalt;
            Reg::get('userMgr')->updateUser($usr);
            
            Reg::get('userSess')->revokeAllSessions($usr->id);
            
            Reg::get('ao')->set('result', "OK");
        }
    }
    catch (Exception $e){
        Reg::get('error')->add(C('Unexpected error!'));
        Reg::get('ao')->setStatusNotOk();
    }
}