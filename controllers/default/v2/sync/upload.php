<?php

use SPSync\v2\SPNotEnoughSpaceException;
use SPSync\v2\SPSyncManager;

isLogined(true, false);

if(!isset($_FILES['file']) || empty($_FILES['file'])){
	Reg::get('error')->add(C('No file uploaded'));
	Reg::get('ao')->setStatusNotOk();
}
if(!isset($_FILES['thumb']) || empty($_FILES['thumb'])){
	Reg::get('error')->add(C('No thumb uploaded'));
	Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
	try{
		$set = (!empty($_POST['set']) ? $_POST['set'] : SPSyncManager::SET_GALLERY);
		$albumId = (!empty($_POST['albumId']) ? $_POST['albumId'] : null);
		$version = (!empty($_POST['version']) ? $_POST['version'] : null);
		$dateCreated = (!empty($_POST['dateCreated']) ? $_POST['dateCreated'] : null);
		$dateModified = (!empty($_POST['dateModified']) ? $_POST['dateModified'] : null);
		$headers = (!empty($_POST['headers']) ? $_POST['headers'] : null);
		
		if(empty($headers) || empty($dateModified) || empty($dateCreated) || empty($version)){
            Reg::get('error')->add(C('Invalid parameters'));
            Reg::get('ao')->setStatusNotOk();
        }
		else {
            
            $isUploadAllowed = false;
            
            if (Reg::get('spsync')->isFileExistsInDb($_FILES['file']['name'], Reg::get('usr'), $set)) {
                $file = Reg::get('spsync')->getFileFromDb($_FILES['file']['name'], Reg::get('usr'), $set);
                if (!empty($file)) {
                    if($version <= $file->version){
                        $version = $file->version + 1;
                    }
                    $isUploadAllowed = true;
                }
            } else {
                $isUploadAllowed = true;
            }
            $isUploadAllowed = true;
            if ($isUploadAllowed) {
                Reg::get('spsync')->uploadFile($_FILES['file'], $_FILES['thumb'], Reg::get('usr'), $set, $albumId, $version, $dateCreated, $dateModified, $headers);
                Reg::get('ao')->set('spaceUsed', Reg::get('usr')->props->spaceUsed);
                Reg::get('ao')->set('spaceQuota', Reg::get('usr')->props->spaceQuota);
            }
        }
	}
	catch(SPNotEnoughSpaceException $e){
        Reg::get('ao')->set('notEnoughSpace', '1');
        Reg::get('ao')->setStatusNotOk();
    }
	catch (Exception $e){
		Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
		Reg::get('ao')->setStatusNotOk();
	}
}