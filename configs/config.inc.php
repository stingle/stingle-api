<?php
require_once ('config.db.inc.php');
require_once ('config.debug.inc.php');
require_once ('config.packages.inc.php');
require_once ('config.site.inc.php');
require_once ('config.system.inc.php');

if(file_exists('configsSite/config.override.inc.php')){
	require_once ('configsSite/config.override.inc.php');
}