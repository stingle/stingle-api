#!/usr/bin/php
<?php
ini_set('max_execution_time','172800');
ini_set('memory_limit','512M');

if(!empty($_SERVER['REMOTE_ADDR'])){
	exit;
}

define("IS_CGI",1);

if(isset($_SERVER['argv']) and count($_SERVER['argv'])){
	for($i=1;$i<count($_SERVER['argv']);$i++){
		$params = explode("=", $_SERVER['argv'][$i]);
		$_GET[$params[0]] = $params[1];
	}
}

include("index.php");
