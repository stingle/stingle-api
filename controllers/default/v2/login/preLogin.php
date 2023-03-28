<?php

$email = (isset($_POST['email'])) ? $_POST['email'] : null;

recordRequest('preLogin');

if(empty($email)){
    Reg::get('error')->add(C('Invalid parameters!'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()) {
    try {
        $usr = Reg::get('userMgr')->getUserByLogin($email);
        Reg::get('ao')->set('salt', $usr->props->pwsalt);
    } catch (UserNotFoundException $e) {
        Reg::get('error')->add(C('Incorrect username/password combination'));
        Reg::get('ao')->setStatusNotOk();
    }
}