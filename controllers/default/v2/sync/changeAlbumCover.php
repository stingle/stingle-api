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
        
        if(empty($params->cover)){
            $params->cover = null;
        }
        
        $userAlbum = Reg::get('spsync')->getAlbumFromDb($params->albumId, Reg::get('usr'));
    
        if(empty($userAlbum)){
            throw new RuntimeException("Album not found!");
        }
    
        if(!$userAlbum->isOwner){
            throw new RuntimeException("You don't have permission to edit this album!");
        }
    
        $now = getMilliseconds();
        
        $currentParticipantIds = Reg::get('spsync')->getAlbumParticipants($userAlbum->albumId);
        
        // Update existing participants albums
        foreach ($currentParticipantIds as $uid) {
            $user = Reg::get('userMgr')->getUserById($uid);
            $partAlbum = Reg::get('spsync')->getAlbumFromDb($userAlbum->albumId, $user);
            
            $partAlbum->cover = $params->cover;
            $partAlbum->dateModified = $now;
    
            Reg::get('spsync')->updateAlbum($partAlbum);
        }
    }
    catch (Exception $e){
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}