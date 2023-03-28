<?php

try{
    $params = getApiRequestSecureParams();

    if(empty($params) || empty($params->keyBundle)){
        throw new RuntimeException("Invalid params!");
    }
    
    Reg::get('spkeys')->updateKeyBundle(Reg::get('usr')->id, $params->keyBundle);
}
catch (Exception $e){
    Reg::get('error')->add(C('Unexpected error!'));
    Reg::get('ao')->setStatusNotOk();
}