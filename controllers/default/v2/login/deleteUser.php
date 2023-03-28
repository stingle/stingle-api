<?php

recordRequest('deleteAccount');
$params = getApiRequestSecureParams();

if(empty($params->password)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

$additionalCredentials = array();
try {
    $user = Reg::get('userAuth')->checkCredentials(Reg::get('usr')->login, $params->password, $additionalCredentials, false);
    if(!Reg::get('userMgr')->deleteUser($user)) {
        Reg::get('ao')->setStatusNotOk();
    }
}
catch (Exception $e) {
    Reg::get('error')->add(C('Incorrect password!') . " - " . format_exception($e));
    Reg::get('ao')->setStatusNotOk();
}
