<?php

use SPSync\v2\SPSyncManager;

isLogined();

if(empty($_POST['file'])){
	Reg::get('ao')->setStatusNotOk();
}
if(!isset($_POST['set']) || !in_array($_POST['set'], SPSyncManager::getConstsArray("SET_"))){
	Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
	try{
		if(Reg::get('spsync')->isFileExistsInDb($_POST['file'], Reg::get('usr'), $_POST['set'])){
			Reg::get('ao')->set('url', Reg::get('spsync')->getFileSignedUrl($_POST['file']));
		}
	}
	catch (Exception $e){
		Reg::get('ao')->setStatusNotOk();
	}
}