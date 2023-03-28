<?php

use SPSync\v2\SPDBAlbum;
use SPSync\v2\SPDBAlbumPermissions;
use SPSync\v2\SPDBContact;
use SPSync\v2\SPSyncManager;

recordRequest('share');

$params = getApiRequestSecureParams();

if(empty($params->album) || empty($params->sharingKeys)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    try{
        $album = json_decode($params->album);
        $sharingKeys = json_decode($params->sharingKeys);
    
        $userAlbum = Reg::get('spsync')->getAlbumFromDb($album->albumId, Reg::get('usr'));
        $currentParticipantIds = Reg::get('spsync')->getAlbumParticipants($userAlbum->albumId, Reg::get('usr'));
        $now = getMilliseconds();
    
        if(empty($userAlbum)){
            throw new RuntimeException("Album not found!");
        }
    
        $isShared = $userAlbum->isShared;
        $isOwner = $userAlbum->isOwner;
        $permissions = $userAlbum->permissions;
        
        // If album is not yet shared it means this is the owner
        if($userAlbum->isShared == 0){
            $isShared = 1;
            $isOwner = 1;
            $permissions = $album->permissions;
        }
        
        // Update sharing user's album
        $userAlbum->metadata = $album->metadata;
        $userAlbum->isShared = (int)$isShared;
        $userAlbum->isHidden = (int)$album->isHidden;
        $userAlbum->isOwner = (int)$isOwner;
        $userAlbum->permissions = $permissions;
        $userAlbum->members = $album->members;
        $userAlbum->dateModified = $now;
        $userAlbum->updatePermissionsObj();
    
        if($isOwner != 1 && !empty($userAlbum->permissionsObj) && !$userAlbum->permissionsObj->allowShare){
            Reg::get('error')->add(C("You don't have permission to share this album"));
            Reg::get('ao')->setStatusNotOk();
        }
        else {
            Reg::get('spsync')->updateAlbum($userAlbum);
    
            // New participants
            foreach ($sharingKeys as $userId => $albumKey) {
                if (in_array($userId, $currentParticipantIds)) {
                    continue;
                }
                $user = Reg::get('userMgr')->getUserById($userId);
                if (!Reg::get('spsync')->isAlbumExistsInDb($userAlbum->albumId, $user)) {
                    $newAlbum = new SPDBAlbum();
                    $newAlbum->userId = $user->id;
                    $newAlbum->albumId = $userAlbum->albumId;
                    $newAlbum->encPrivateKey = $albumKey;
                    $newAlbum->publicKey = $userAlbum->publicKey;
                    $newAlbum->metadata = $album->metadata;
            
                    $newAlbum->isShared = 1;
                    $newAlbum->isHidden = 1;
                    $newAlbum->isOwner = 0;
                    $newAlbum->permissions = $permissions;
                    $newAlbum->members = $album->members;
                    $newAlbum->isLocked = $userAlbum->isLocked;
                    $newAlbum->cover = $userAlbum->cover;
            
                    $newAlbum->dateCreated = $now;
                    $newAlbum->dateModified = $now;
            
                    Reg::get('spsync')->addAlbumToDb($newAlbum);
            
                    Reg::get('spsync')->insertMutualContacts(Reg::get('usr'), $user);
                    foreach ($currentParticipantIds as $uid) {
                        $part = Reg::get('userMgr')->getUserById($uid);
                        Reg::get('spsync')->insertMutualContacts($part, $user);
                    }
                }
            }
    
            // Update existing participants albums
            foreach ($currentParticipantIds as $uid) {
                $part = Reg::get('userMgr')->getUserById($uid);
                $partAlbum = Reg::get('spsync')->getAlbumFromDb($album->albumId, $part);
        
                $partAlbum->permissions = $permissions;
                $partAlbum->members = $album->members;
                $partAlbum->dateModified = $now;
        
                Reg::get('spsync')->updateAlbum($partAlbum);
            }
    
            Reg::get('spsync')->updateAlbumFilesModifiedTime($album->albumId);
        }
    }
    catch (Exception $e){
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}