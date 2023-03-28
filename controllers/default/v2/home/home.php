<?php
Reg::get('packageMgr')->usePlugin("Output", "Smarty");
SmartyWrapper::replaceMainOutputWithSmarty();

$color = "green";
$status = "All systems are operational.";
if(!healthCheck()){
    $color = "red";
    $status = "We are having technical difficulties at the moment. Maintenance have been notified.";
}

Reg::get('smarty')->assign("color", $color);
Reg::get('smarty')->assign("status", $status);
