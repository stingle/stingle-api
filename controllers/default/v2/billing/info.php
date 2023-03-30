<?php
$params = getApiRequestSecureParams();
if(empty($params->rand)){
    Reg::get('error')->add(C('Invalid parameters'));
    Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
    Reg::get('ao')->set('plan', "free");
    Reg::get('ao')->set('expiration', null);
    Reg::get('ao')->set('spaceUsed', Reg::get('usr')->props->spaceUsed);
    Reg::get('ao')->set('spaceQuota', Reg::get('usr')->props->spaceQuota);
    Reg::get('ao')->set('isManual', "0");
    Reg::get('ao')->set('paymentGw', null);
}