<?php

use SPSync\v2\SPNotEnoughSpaceException;
use SPSync\v2\SPPermissionCheckFailureException;
use SPSync\v2\SPSyncManager;

$params = getApiRequestSecureParams();

if(empty($params->count) ||
    !is_numeric($params->count) ||
    !isset($params->setFrom) ||
    !isset($params->setTo) ||
    !isset($params->isMoving)){
    
	Reg::get('error')->add(C('Invalid parameters'));
	Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
	try{
		for($i=0;$i<$params->count;$i++){
		    $paramName = 'filename' . $i;
		    $headersName = 'headers' . $i;
			if(!empty($params->$paramName)){
                if($params->setFrom == SPSyncManager::SET_GALLERY && $params->setTo == SPSyncManager::SET_TRASH) {
                    Reg::get('spsync')->moveFileFromGalleryToTrash($params->$paramName, Reg::get('usr'));
                }
                elseif($params->setFrom == SPSyncManager::SET_TRASH && $params->setTo == SPSyncManager::SET_GALLERY) {
                    Reg::get('spsync')->moveFileFromTrashToGallery($params->$paramName, Reg::get('usr'));
                }
                if($params->setFrom == SPSyncManager::SET_GALLERY && $params->setTo == SPSyncManager::SET_ALBUM) {
                    if (empty($params->albumIdTo) || empty($params->$headersName)) {
                        throw new InvalidArgumentException("Invalid arguments!");
                    }
                    Reg::get('spsync')->moveFileFromGalleryToAlbum($params->$paramName, Reg::get('usr'), $params->albumIdTo, $params->$headersName, $params->isMoving);
                }
                elseif($params->setFrom == SPSyncManager::SET_ALBUM && $params->setTo == SPSyncManager::SET_GALLERY) {
                    if (empty($params->albumIdFrom) || empty($params->$headersName)) {
                        throw new InvalidArgumentException("Invalid arguments!");
                    }
                    Reg::get('spsync')->moveFileFromAlbumToGallery($params->$paramName, Reg::get('usr'), $params->albumIdFrom, $params->$headersName, $params->isMoving);
                }
                elseif($params->setFrom == SPSyncManager::SET_ALBUM && $params->setTo == SPSyncManager::SET_ALBUM) {
                    if (empty($params->albumIdFrom) || empty($params->albumIdTo) || empty($params->$headersName)) {
                        throw new InvalidArgumentException("Invalid arguments!");
                    }
                    Reg::get('spsync')->moveFileFromAlbumToAlbum($params->$paramName, Reg::get('usr'), $params->albumIdFrom, $params->albumIdTo, $params->$headersName, $params->isMoving);
                }
                elseif($params->setFrom == SPSyncManager::SET_ALBUM && $params->setTo == SPSyncManager::SET_TRASH) {
                    if (empty($params->albumIdFrom) || empty($params->$headersName)) {
                        throw new InvalidArgumentException("Invalid arguments!");
                    }
                    Reg::get('spsync')->moveFileFromAlbumToTrash($params->$paramName, Reg::get('usr'), $params->albumIdFrom, $params->$headersName);
                }
                
				
			}
		}
	}
	catch (Exception $e){
        //Reg::get('error')->add(C($e->getMessage()));
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
	}
}