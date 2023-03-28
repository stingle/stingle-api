<?php
use SPSync\v2\SPSyncManager;

isLogined();

if(!isset($_POST['filesST']) || !is_numeric($_POST['filesST']) || $_POST['filesST'] < 0){
	Reg::get('error')->add(C('Invalid parameters'));
	Reg::get('ao')->setStatusNotOk();
}
if(!isset($_POST['trashST']) || !is_numeric($_POST['trashST']) || $_POST['trashST'] < 0){
	Reg::get('error')->add(C('Invalid parameters'));
	Reg::get('ao')->setStatusNotOk();
}
if(!isset($_POST['albumsST']) || !is_numeric($_POST['albumsST']) || $_POST['albumsST'] < 0){
	Reg::get('error')->add(C('Invalid parameters'));
	Reg::get('ao')->setStatusNotOk();
}
if(!isset($_POST['albumFilesST']) || !is_numeric($_POST['albumFilesST']) || $_POST['albumFilesST'] < 0){
	Reg::get('error')->add(C('Invalid parameters'));
	Reg::get('ao')->setStatusNotOk();
}
if(!isset($_POST['delST']) || !is_numeric($_POST['delST']) || $_POST['delST'] < 0){
	Reg::get('error')->add(C('Invalid parameters'));
	Reg::get('ao')->setStatusNotOk();
}
if(!isset($_POST['cntST']) || !is_numeric($_POST['cntST']) || $_POST['cntST'] < 0){
	Reg::get('error')->add(C('Invalid parameters'));
	Reg::get('ao')->setStatusNotOk();
}


if(Reg::get('ao')->isStatusOk()){
	try{
        //Reg::get('spsync')->createIndices();
        Reg::get('ao')->set('files', Reg::get('spsync')->getFilesListHelper(Reg::get('usr'), $_POST['filesST'], SPSyncManager::SET_GALLERY));
        Reg::get('ao')->set('trash', Reg::get('spsync')->getFilesListHelper(Reg::get('usr'), $_POST['trashST'], SPSyncManager::SET_TRASH));
        Reg::get('ao')->set('albums', Reg::get('spsync')->getAlbumsListAfterDate(Reg::get('usr'), $_POST['albumsST']));
        Reg::get('ao')->set('albumFiles', Reg::get('spsync')->getFilesListHelper(Reg::get('usr'), $_POST['albumFilesST'], SPSyncManager::SET_ALBUM));
        Reg::get('ao')->set('deletes', Reg::get('spsync')->getDeleteEvents(Reg::get('usr'), $_POST['delST']));
        Reg::get('ao')->set('contacts', Reg::get('spsync')->getContactsListHelper(Reg::get('usr'), $_POST['cntST']));
        Reg::get('ao')->set('spaceUsed', Reg::get('usr')->props->spaceUsed);
        Reg::get('ao')->set('spaceQuota', Reg::get('usr')->props->spaceQuota);
	}
	catch (Exception $e){
		//Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('error')->add(C($e->getMessage()));
		Reg::get('ao')->setStatusNotOk();
		recordException($e);
	}
}