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
    
        if($userAlbum->isOwner || !$userAlbum->isShared){
            throw new RuntimeException("You can't leave your own album!");
        }
    
        $now = getMilliseconds();
    
        
        $cnt = Reg::get('spsync')->removeAlbumFromDb($userAlbum->albumId, Reg::get('usr'), false);
        Reg::get('ao')->set('cnt', $cnt);
    
        $currentParticipantIds = Reg::get('spsync')->getAlbumParticipants($params->albumId);
    
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