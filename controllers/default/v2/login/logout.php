<?php

isLogined();

if(empty($_POST['token'])){
    Reg::get('error')->add(C('Invalid arguments. Empty token'));
    Reg::get('ao')->setStatusNotOk();
}

Reg::get('userSess')->revokeToken($_POST['token']);