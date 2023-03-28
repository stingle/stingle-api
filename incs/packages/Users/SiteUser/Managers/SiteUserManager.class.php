<?php

use SPSync\v2\SPDBAlbum;
use SPSync\v2\SPPermissionCheckFailureException;
use SPSync\v2\SPSyncManager;

class SiteUserManager extends UserManagerCaching{
	
	const GROUP_ADMINS = "admins";
	const GROUP_USERS 	= 'users';
	
	const GOOGLE_AUTH_YES = 1;
	const GOOGLE_AUTH_NO = 0;
	
	public function __construct($dbInstanceKey = null){
		parent::__construct($dbInstanceKey);
	}
	
	public function getUserByLogin($login){
		$filter = new UsersFilter();
		$filter->setLogin($login);
		return $this->getUser($filter);
	}
	
	
    public static function sanitizeUserObject(User $user){
        $user->password = '';
        $user->salt = '';
        $user->props->pwsalt = '';
        $user->props->purchaseToken = '';
    }
    
    public function deleteUserFiles(User $user){
        $debugOutput = "";
        $debugOutput .= "Deleting user files {$user->login} with ID {$user->id}\n\n";
        $files = Reg::get('spsync')->getFilesListHelper($user, null, SPSyncManager::SET_GALLERY);
        foreach($files as $file){
            Reg::get('spsync')->deleteFile($file['file'], $user, SPSyncManager::SET_GALLERY, true);
            $debugOutput .= "Deleted MAIN - " . $file['file'] . "\n";
        }
    
        $albumFiles = Reg::get('spsync')->getFilesListHelper($user, null, SPSyncManager::SET_ALBUM);
        foreach($albumFiles as $file){
            Reg::get('spsync')->deleteFile($file['file'], $user, SPSyncManager::SET_ALBUM, true);
            $debugOutput .= "Deleted ALBUM FILE - " . $file['file'] . "\n";
        }
    
        $albums = Reg::get('spsync')->getAlbumsListAfterDate($user, null);
        foreach($albums as $album){
            try {
                if($album['isOwner']) {
                    Reg::get('spsync')->removeAlbumFromDb($album['albumId'], $user, true);
                }
                else{
                    Reg::get('spsync')->removeAlbumFromDb($album['albumId'], $user, false);
                    $currentParticipantIds = Reg::get('spsync')->getAlbumParticipants($album['albumId']);
                    $membersStr = implode(",", $currentParticipantIds);
                
                    // Update existing participants albums
                    foreach ($currentParticipantIds as $uid) {
                        $partAlbum = Reg::get('spsync')->getAlbumFromDb($album['albumId'], Reg::get('userMgr')->getUserById($uid));
                    
                        $partAlbum->members = $membersStr;
                        $partAlbum->dateModified = getMilliseconds();
                    
                        Reg::get('spsync')->updateAlbum($partAlbum);
                    }
                }
            }
            catch (SPPermissionCheckFailureException $e){}
            $debugOutput .= "Deleted album - " . $album['albumId'] . "\n";
        }
    
        $trash = Reg::get('spsync')->getFilesListHelper($user, null, SPSyncManager::SET_TRASH);
        foreach($trash as $file){
            Reg::get('spsync')->deleteFile($file['file'], $user, SPSyncManager::SET_TRASH, true);
            $debugOutput .= "Deleted TRASH - " . $file['file'] . "\n";
        }
        Reg::get('spsync')->deleteActualFiles();
    
        $count = Reg::get('spsync')->removeDeleteEventsFromDb($user);
        $debugOutput .= "Deleted $count delete events from db\n";
    
        return $debugOutput;
    }
    
    public function deleteUser(User $user, $output = "") {
        $debugOutput = $output;
        
        $debugOutput .= $this->deleteUserFiles($user);
    
        $count = Reg::get('spsync')->removeUserFromEverybodyContacts($user);
        $debugOutput .= "Deleted $count contacts from db\n";
    
        $count = Reg::get('userSess')->revokeAllSessions($user->id);
        $debugOutput .= "Revoked $count sessions\n";
    
        $count = Reg::get('spkeys')->deleteKeyBundle($user->id);
        $debugOutput .= "Deleted $count key bundles\n";
    
        $ret = HookManager::callHook("BeforeUserDelete", $user);
        if(!empty($ret)) {
            foreach ($ret as $key => $val) {
                $debugOutput .= $val;
            }
        }
        
        $count = parent::deleteUser($user);
        $debugOutput .= "Deleted $count users\n";
    
        DBLogger::logCustom("UserDelete", $debugOutput);
        
        return $count;
    }
}
