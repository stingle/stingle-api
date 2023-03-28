<?php

use SPSync\v2\SPSyncManager;

isLogined();

$isThumb = true;
if(empty($_POST['file'])){
    Reg::get('ao')->setStatusNotOk();
}
if(!isset($_POST['set']) || !in_array($_POST['set'], SPSyncManager::getConstsArray("SET_"))){
    Reg::get('ao')->setStatusNotOk();
}
if(isset($_POST['is_thumb'])){
    $isThumb = $_POST['is_thumb'] === "1";
}
$albumId = null;
if($_POST['set'] == SPSyncManager::SET_ALBUM){
    if(!empty($_POST['albumId'])) {
        $albumId = $_POST['albumId'];
    }
    else {
        Reg::get('ao')->setStatusNotOk();
    }
}

if(Reg::get('ao')->isStatusOk()){
	try{
        if (Reg::get('spsync')->isFileExistsInDb($_POST['file'], Reg::get('usr'), $_POST['set'], $albumId)) {
            if($isThumb) {
                $url = Reg::get('spsync')->getThumbSignedUrl($_POST['file']);
            }
            else{
                $url = Reg::get('spsync')->getFileSignedUrl($_POST['file']);
            }
            if(!empty($url)){
                redirect($url);
            }
        }
	}
	catch (Exception $e){
		Reg::get('ao')->setStatusNotOk();
	}
}