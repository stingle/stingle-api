<?php

use SPSync\v2\SPDBAlbum;
use SPSync\v2\SPDBAlbumPermissions;
use SPSync\v2\SPDBContact;
use SPSync\v2\SPSyncManager;

$params = getApiRequestSecureParams();

if(empty($params->albumId)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    try{
        
        $userAlbum = Reg::get('spsync')->getAlbumFromDb($params->albumId, Reg::get('usr'));
    
        if(empty($userAlbum)){
            throw new RuntimeException("Album not found!");
        }
    
        if(!$userAlbum->isOwner || !$userAlbum->isShared){
            throw new RuntimeException("You don't have permission to edit this album!");
        }
    
        $currentParticipantIds = Reg::get('spsync')->getAlbumParticipants($userAlbum->albumId);
    
        foreach ($currentParticipantIds as $uid) {
            if($uid == Reg::get('usr')->id){
                continue;
            }
            $user = Reg::get('userMgr')->getUserById($uid);
        
            Reg::get('spsync')->removeAlbumFromDb($userAlbum->albumId, $user, false);
        }
        
        $now = getMilliseconds();
        $userAlbum->isShared = 0;
        $userAlbum->isHidden = 0;
        $userAlbum->permissions = null;
        $userAlbum->members = null;
        $userAlbum->dateModified = $now;
        
        Reg::get('spsync')->updateAlbum($userAlbum);
    }
    catch (Exception $e){
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}