<?php
Reg::get('requestLimiter')->parseLogForFloodingIps();
echo "Done\n";

exit;