<?php
isLogined();

$keyBundle = Reg::get('spkeys')->getKeyBundleByUserId(Reg::get('usr')->id);
Reg::get('ao')->set('serverPK', base64_encode($keyBundle->serverPK));