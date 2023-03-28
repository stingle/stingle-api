<?php

use SPSync\v2\SPSyncManager;

isLogined();

$isThumb = true;
if(empty($_POST['files']) || !is_array($_POST['files']) || count($_POST['files']) == 0){
	Reg::get('ao')->setStatusNotOk();
}
if(isset($_POST['is_thumb'])){
    $isThumb = $_POST['is_thumb'] === "1";
}

if(Reg::get('ao')->isStatusOk()){
	try{
	    $urls = [];
	    $setsArray = SPSyncManager::getConstsArray("SET_");
	    foreach ($_POST['files'] as $file) {
            if(!isset($file['set']) || !in_array($file['set'], $setsArray)){
                continue;
            }
            if (Reg::get('spsync')->isFileExistsInDb($file['filename'], Reg::get('usr'), $file['set'])) {
                if($isThumb) {
                    $urls[$file['filename']] = Reg::get('spsync')->getThumbSignedUrl($file['filename']);
                }
                else{
                    $urls[$file['filename']] = Reg::get('spsync')->getFileSignedUrl($file['filename']);
                }
            }
        }
        Reg::get('ao')->set('urls', $urls);
	}
	catch (Exception $e){
		Reg::get('ao')->setStatusNotOk();
	}
}