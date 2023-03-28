<?php

if(!empty($_GET['linkId'])){
    $result = Reg::get('linkShortener')->handleLink($_GET['linkId']);
    if(!$result){
        redirect(SITE_PATH);
    }
}

redirect(glink());