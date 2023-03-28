<?php

use SPSync\v2\SPDBAlbum;
use SPSync\v2\SPDBAlbumPermissions;
use SPSync\v2\SPDBContact;
use SPSync\v2\SPSyncManager;

$params = getApiRequestSecureParams();

if(empty($params->album)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    try{
        $album = json_decode($params->album);
    
        $userAlbum = Reg::get('spsync')->getAlbumFromDb($album->albumId, Reg::get('usr'));
    
        if(empty($userAlbum)){
            throw new RuntimeException("Album not found!");
        }
    
        if(!$userAlbum->isOwner || !$userAlbum->isShared){
            throw new RuntimeException("You don't have permission to edit this album!");
        }
    
        $now = getMilliseconds();
        $permissions = $album->permissions;
        
        $currentParticipantIds = Reg::get('spsync')->getAlbumParticipants($userAlbum->albumId);
    
        // Update existing participants albums
        foreach ($currentParticipantIds as $uid) {
            $partAlbum = Reg::get('spsync')->getAlbumFromDb($userAlbum->albumId, Reg::get('userMgr')->getUserById($uid));
    
            $partAlbum->permissions = $permissions;
            $partAlbum->dateModified = $now;
    
            Reg::get('spsync')->updateAlbum($partAlbum);
        }
    }
    catch (Exception $e){
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}