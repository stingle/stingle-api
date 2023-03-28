<?php
Reg::get('packageMgr')->usePlugin('Users', 'UserValidation');
Reg::get('packageMgr')->usePlugin('Security', 'OneTimeCodes');

recordRequest('reg');

$email 			= (isset($_POST['email'])) ? $_POST['email'] : null;
$password		= (isset($_POST['password'])) ? $_POST['password'] : null;
$salt			= (isset($_POST['salt'])) ? $_POST['salt'] : null;
$keyBundle		= (isset($_POST['keyBundle'])) ? $_POST['keyBundle'] : null;

Reg::get('userValidation')->checkEmail($email);

Reg::get('userValidation')->checkPassword($password);
Reg::get('userValidation')->checkSalt($salt);
Reg::get('userValidation')->checkKeyBundle($keyBundle);

if(Reg::get('userValidation')->hasError()){
    foreach(Reg::get('userValidation')->getErrors() as $err){
        Reg::get('error')->add($err);
    }
    
    Reg::get('ao')->setStatusNotOk();
}
else{
    $defaultQuota = ConfigManager::getConfig('Users', 'SiteUser')->AuxConfig->defaultQuota;
    
    
    $usrObj = new User();
    $props = new UserProperties();
    $usrObj->props = $props;
    
    $usrObj->login			= $email;
    $usrObj->password		= $password;
    $usrObj->email			= $email;
    $usrObj->props->pwsalt	= $salt;
    $usrObj->props->baseQuota = $defaultQuota;
    $usrObj->props->spaceQuota = $defaultQuota;
    
    try{
        $usr = Reg::get('userMgr')->createUser($usrObj);
        
        Reg::get('spkeys')->insertKeyBundle($usr->id, $keyBundle);
        
        HookManager::callHook("SignUpComplete", $usr);
        
        Reg::get('userGroupsMgr')->addUserToGroup($usr, Reg::get('userGroupsMgr')->getGroupByName(SiteUserManager::GROUP_USERS));
        
        $token = Reg::get('userSess')->addSession($usr->id);
        
        Reg::get('ao')->set('token', $token);
        
        $homeFolder = sha1($usr->id);
        Reg::get('ao')->set('homeFolder', $homeFolder);
        Reg::get('ao')->set('userId', $usr->id);
    }
    catch (UserNotFoundException $e){
        //Reg::get('error')->add(C('Error: User does not exist'));
        Reg::get('error')->add(format_exception($e));
        Reg::get('ao')->setStatusNotOk();
    }
}
