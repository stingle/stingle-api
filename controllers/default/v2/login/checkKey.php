<?php

$email = (isset($_POST['email'])) ? $_POST['email'] : null;

recordRequest('checkKey');

if(!Reg::get('userMgr')->isLoginExists($email)){
    Reg::get('error')->add(C('User with specified email doesn\'t exist'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    $usr = Reg::get('userMgr')->getUserByLogin($email);
    $keyBundle = Reg::get('spkeys')->getKeyBundleByUserId($usr->id);
    
    $msg = 'validkey_' . generateRandomString(16);
    
    Reg::get('ao')->set('challenge', base64_encode(sodium_crypto_box_seal($msg, $keyBundle->publicKey)));
    Reg::get('ao')->set('serverPK', base64_encode($keyBundle->serverPK));
    Reg::get('ao')->set('isKeyBackedUp', !empty($keyBundle->privateKey) ? 1 : 0);
}