<?php

echo "Cleaned Up " . Reg::get('otc')->cleanUp() . " security codes\n\n";
echo "Released " . Reg::get('requestLimiter')->releaseIPs() . " blocked IPs\n\n";
// Needs a framework version with DBLogger::purgeRetention()
if(method_exists('DBLogger', 'purgeRetention')){
	echo "Purged " . DBLogger::purgeRetention() . " expired log rows\n\n";
}

exit;
