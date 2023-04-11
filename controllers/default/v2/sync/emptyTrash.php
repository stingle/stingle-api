<?php

use SPSync\v2\SPSyncManager;

$params = getApiRequestSecureParams();
if(!empty($params) && !empty($params->time)){
    
    
    $seconds = round(microtime(true));
    $remoteSeconds = round($params->time / 1000);
    
    $diff = abs($seconds - $remoteSeconds);
    
    if ($diff > 60) {
        Reg::get('error')->add(C("Something went wrong. Please adjust your device's clock and try again."));
        Reg::get('ao')->setStatusNotOk();
    }
    
    try {
        $trashedFiles = Reg::get('spsync')->getFilesListHelper(Reg::get('usr'), null, SPSyncManager::SET_TRASH);
        foreach ($trashedFiles as $file) {
            Reg::get('spsync')->deleteFile($file['file'], Reg::get('usr'));
        }
        Reg::get('spsync')->deleteActualFiles();
    } catch (Exception $e) {
        Reg::get('error')->add(C("Something went wrong.\n" . format_exception($e)));
        Reg::get('ao')->setStatusNotOk();
    }
}
else{
    Reg::get('error')->add(C("Invalid parameters."));
    Reg::get('ao')->setStatusNotOk();
}