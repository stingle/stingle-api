<?php

Reg::get('packageMgr')->usePlugin('Users', 'UserValidation');
$params = getApiRequestSecureParams();

if(empty($params->newEmail)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}



if(Reg::get('ao')->isStatusOk()){
    try{
        Reg::get('userValidation')->checkEmail($params->newEmail);
        if (Reg::get('userValidation')->hasError()) {
            foreach (Reg::get('userValidation')->getErrors() as $err) {
                Reg::get('error')->add($err);
            }
        
            Reg::get('ao')->setStatusNotOk();
        }
        else {
            Reg::get('usr')->login = $params->newEmail;
            Reg::get('usr')->email = $params->newEmail;
    
            Reg::get('userMgr')->updateUser(Reg::get('usr'));
            
            $usr = Reg::get('usr');
            HookManager::callHook('EmailChanged',$usr);
            
            Reg::get('ao')->set('email', $params->newEmail);
        }
    }
    catch (Exception $e){
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}