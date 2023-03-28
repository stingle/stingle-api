<?php

use SPSync\v2\SPSyncManager;

recordRequest('contact');

isLogined();

$paramsStr       = (isset($_POST['params'])) ? $_POST['params'] : null;
$keyBundleObj = Reg::get('spkeys')->getKeyBundleByUserId(Reg::get('usr')->id);
$params = SPKeyManager::getParamsFromEncMessage($paramsStr, $keyBundleObj);

if(empty($params->email)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    try{
        $filter = new UsersFilter();
        $filter->setEmail($params->email);
        $filter->setUserIdNotEqual(Reg::get('usr')->id);
        
        $users = Reg::get('userMgr')->getUsersList($filter);
        
        if(count($users) == 1){
            $user = $users[0];
            $userKeyBundle = Reg::get('spkeys')->getKeyBundleByUserId($user->id);
    
            $contact = [
                'userId' => $user->id,
                'email' => $user->email,
                'publicKey' => base64_encode($userKeyBundle->publicKey)
            ];
            
            Reg::get('ao')->set('contact', $contact);
        }
        else{
            Reg::get('error')->add(C("User not found."));
            Reg::get('ao')->setStatusNotOk();
        }
    }
    catch (Exception $e){
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}