<?php

use SPSync\v2\SPDBAlbum;
use SPSync\v2\SPDBAlbumPermissions;
use SPSync\v2\SPDBContact;
use SPSync\v2\SPSyncManager;

$params = getApiRequestSecureParams();

if(empty($params->album) || empty($params->memberUserId)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    try{
        if($params->memberUserId == Reg::get('usr')->id){
            throw new RuntimeException("Can't remove yourself!");
        }
        
        $album = json_decode($params->album);
    
        $userAlbum = Reg::get('spsync')->getAlbumFromDb($album->albumId, Reg::get('usr'));
        $member = Reg::get('userMgr')->getUserById($params->memberUserId);
    
        if(empty($userAlbum)){
            throw new RuntimeException("Album not found!");
        }
    
        if(!$userAlbum->isOwner || !$userAlbum->isShared){
            throw new RuntimeException("You don't have permission to edit this album!");
        }
    
        $now = getMilliseconds();
        
        Reg::get('spsync')->removeAlbumFromDb($userAlbum->albumId, $member, false);
    
    
        $currentParticipantIds = Reg::get('spsync')->getAlbumParticipants($userAlbum->albumId);
        
        $membersStr = implode(",", $currentParticipantIds);
    
        // Update existing participants albums
        foreach ($currentParticipantIds as $uid) {
            $partAlbum = Reg::get('spsync')->getAlbumFromDb($userAlbum->albumId, Reg::get('userMgr')->getUserById($uid));
    
            $partAlbum->members = $membersStr;
            $partAlbum->dateModified = $now;
    
            Reg::get('spsync')->updateAlbum($partAlbum);
        }
    }
    catch (Exception $e){
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}