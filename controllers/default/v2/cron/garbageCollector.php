<?php

echo "Cleaned Up " . Reg::get('otc')->cleanUp() . " security codes\n\n";
echo "Released " . Reg::get('requestLimiter')->releaseIPs() . " blocked IPs\n\n";

exit;