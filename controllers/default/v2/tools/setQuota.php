<?php

if(empty($_GET['email']) && empty($_GET['quota'])){
    echo 'Usage: setQuota email="test@stingle.org" quota=1T' . "\n";
    exit;
}

if(empty($_GET['email'])){
    echo "Invalid email\n";
    exit;
}

try {
    $user = Reg::get('userMgr')->getUserByLogin($_GET['email']);
}
catch(UserNotFoundException $e){
    echo "No such user\n";
    exit;
}

$unit = substr($_GET['quota'], -1);
if($unit != 'T' && $unit != 'G'){
    echo "quota have to end with T or G\n";
    exit;
}

$number = substr($_GET['quota'], 0, -1);
if($unit == 'G') {
    $quotaMb = $number * 1024;
}
elseif($unit == 'T'){
    $quotaMb = $number * 1024 * 1024;
}
StingleBilling::setBaseQuota($user, $quotaMb);

echo "Success\n";
exit;