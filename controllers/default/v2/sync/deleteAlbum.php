<?php

$params = getApiRequestSecureParams();

if(empty($params->albumId)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    try{
        if(Reg::get('spsync')->isAlbumExistsInDb($params->albumId, Reg::get('usr'))){
            Reg::get('spsync')->removeAlbumFromDb($params->albumId, Reg::get('usr'), true);
        }
    }
    catch (Exception $e){
        Reg::get('error')->add(C("Something went wrong.\n" . $e->getMessage()));
        recordException($e);
        Reg::get('ao')->setStatusNotOk();
    }
}