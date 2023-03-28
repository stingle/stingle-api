<?php

use SPSync\v2\SPDBAlbum;

$params = getApiRequestSecureParams();

if(empty($params->albumId) || empty($params->encPrivateKey) || empty($params->publicKey) || empty($params->metadata) || empty($params->dateCreated) || empty($params->dateModified)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    try{
        if(!Reg::get('spsync')->isAlbumExistsInDb($params->albumId, Reg::get('usr'))){
            $album = new SPDBAlbum();
            $album->userId = Reg::get('usr')->id;
            $album->albumId = $params->albumId;
            $album->encPrivateKey = $params->encPrivateKey;
            $album->publicKey = $params->publicKey;
            $album->metadata = $params->metadata;
            $album->dateCreated = $params->dateCreated;
            $album->dateModified = $params->dateModified;
            
            $num = Reg::get('spsync')->addAlbumToDb($album);
            if($num == 0){
                Reg::get('ao')->setStatusNotOk();
            }
        }
    }
    catch (Exception $e){
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}