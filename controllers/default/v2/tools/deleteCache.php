<?php

if(!empty($_GET['rmcache'])){
    //exec('rm -R cache/templates_compile/*');
    
    exec('rm -fv cache/stingle_cache/*', $output);
    echo "\n" . implode("\n", $output) . "\n\n";
    
    if(!defined('DISABLE_APCU') && extension_loaded('apcu')){
        exec('service apache2 reload');
        //exec('systemctl reload httpd');
        echo "Realoaded apache\n";
    }
}

if(!empty($_GET['composer'])){
    exec('./composer.phar install -n');
}
if(!empty($_GET['memcache'])){
    Reg::get('memcache')->clearAllItems();
    echo "\nFlushed memcache\n\n";
}

exit;