<?php

$email = (isset($_POST['email'])) ? $_POST['email'] : null;
$password = (isset($_POST['password'])) ? $_POST['password'] : null;

recordRequest('login');

$additionalCredentials = array();
try {
    $previousLoginIp = null;
    $hasNewLoginListeners = HookManager::isAnyHooksRegistered('UserLoginFromNewIp');
    if($hasNewLoginListeners && !empty($email)){
        try {
            if(Reg::get('userMgr')->isLoginExists($email)){
                $previousUser = Reg::get('userMgr')->getUserByLogin($email);
                $previousLoginIp = $previousUser->lastLoginIP;
            }
        }
        catch (Throwable $e) {
            // Login must remain available if the optional notification lookup fails.
        }
    }

    $usr = Reg::get('userAuth')->checkCredentials($email, $password, $additionalCredentials, false);
    Reg::register("usr", $usr, true);
    
    $token = Reg::get('userSess')->addSession($usr->id);
    Reg::get('ao')->set('token', $token);

    $keyBundle = Reg::get('spkeys')->getKeyBundleByUserId($usr->id);
    if(!empty($keyBundle)) {
        Reg::get('ao')->set('keyBundle', $keyBundle->raw);
        Reg::get('ao')->set('isKeyBackedUp', !empty($keyBundle->privateKey) ? 1 : 0);
        Reg::get('ao')->set('serverPublicKey', base64_encode($keyBundle->serverPK));
    
        $homeFolder = sha1(Reg::get('usr')->id);
        Reg::get('ao')->set('homeFolder', $homeFolder);
        Reg::get('ao')->set('userId', Reg::get('usr')->id);
        Reg::get('ao')->set('addons', AddonManager::getAddonNames());

        $currentLoginIp = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : null;
        if($hasNewLoginListeners && !empty($previousLoginIp) && !empty($currentLoginIp) && $previousLoginIp !== $currentLoginIp){
            $event = array(
                'user' => $usr,
                'ip' => $currentLoginIp,
                'eventTime' => getMilliseconds()
            );
            HookManager::callHook('UserLoginFromNewIp', $event);
        }
    }
    else {
        Reg::get('error')->add(C('Invalid user, please contact support'));
        Reg::get('ao')->setStatusNotOk();
    }
}
catch (YubikeyRequiredException | GoogleAuthRequiredException | GoogleAuthSetupRequiredException $e) {
    Reg::get('ao')->set('needSecondFactor', 1);
    Reg::get('ao')->setStatusNotOk();
}
catch (UserAuthFailedException | UserDisabledException | RequestLimiterTooManyAuthTriesException $e) {
    Reg::get('error')->add(C('Incorrect username/password combination'));
    Reg::get('ao')->setStatusNotOk();
}
