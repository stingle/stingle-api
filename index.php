<?php
require __DIR__ . '/vendor/autoload.php';

define ("STINGLE_PATH", dirname(__FILE__) . "/vendor/alexamiryan/stingle/");
define ("SITE_PACKAGES_PATH", dirname(__FILE__) . "/incs/packages/");
define ("SITE_CONFIGS_PATH", dirname(__FILE__) . "/configs/");
define ("ADDONS_FOLDER_PATH", dirname(__FILE__) . "/addons/");

//define('DISABLE_APCU', true);

require_once 'incs/custom.inc.php';

require_once (STINGLE_PATH . "index.php");
