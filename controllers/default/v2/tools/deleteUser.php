<?php
ob_end_flush();

if(empty($_GET['email'])){
    echo 'Usage: deleteUser email="test@stingle.org"' . "\n";
    exit;
}

try {
    $user = Reg::get('userMgr')->getUserByLogin($_GET['email']);
}
catch(UserNotFoundException $e){
    echo "No such user\n";
    exit;
}

if(Reg::get('userMgr')->deleteUser($user)) {
    echo "Success\n";
}
else{
    echo "Failed\n";
}

exit;