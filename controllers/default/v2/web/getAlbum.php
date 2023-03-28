<?php
exit;
header("Access-Control-Allow-Origin: *");
if(empty($_GET['id']) || $_GET['id'] !== 'g3OjAViIGAkzVkFJoBATaJk9v3jPMDLvWDzWJKYmeRA'){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()) {
    try {
        $albumFiles = Reg::get('spsync')->getAlbumFilesByAlbumId($_GET['id']);
        
        foreach ($albumFiles as &$file) {
            $file['url'] = Reg::get('spsync')->getFileSignedUrl($file['file']);
            $file['urlThumb'] = Reg::get('spsync')->getThumbSignedUrl($file['file']);
        }
        Reg::get('ao')->set('files', $albumFiles);
    } catch (Exception $e) {
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}