<?php

use SPSync\v2\SPSyncManager;

$params = getApiRequestSecureParams();

if(empty($params->count) || !is_numeric($params->count)){
	Reg::get('error')->add(C('Invalid parameters'));
	Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
	try{
		for($i=0;$i<$params->count;$i++){
            $paramName = 'filename' . $i;
			if(!empty($params->$paramName)){
			    Reg::get('spsync')->deleteFile($params->$paramName, Reg::get('usr'));
			}
		}
        Reg::get('spsync')->deleteActualFiles();
	}
	catch (Exception $e){
		Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
		Reg::get('ao')->setStatusNotOk();
	}
}