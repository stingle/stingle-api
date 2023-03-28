<?php
echo base64_encode(sodium_crypto_secretbox_keygen()) . "\n";
exit;