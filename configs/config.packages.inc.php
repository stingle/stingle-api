<?php

// Packages config
$CONFIG['Packages'][] = array("SiteNavigation", "SiteNavigation;APIVersioning");
$CONFIG['Packages'][] = array("Db", "Memcache;QueryBuilder;Migrations");
$CONFIG['Packages'][] = array("Security","Security;OneTimeCodes;RequestLimiter");
$CONFIG['Packages'][] = array("Users", "SiteUser;UserSessions");
$CONFIG['Packages'][] = array("Language", "Language");
$CONFIG['Packages'][] = array("Output", "ApiOutput");
$CONFIG['Packages'][] = array("RewriteURL", "RewriteURL");
$CONFIG['Packages'][] = array("Pager", "MysqlPager");
$CONFIG['Packages'][] = array("Host", "Host");
$CONFIG['Packages'][] = array("Info");
$CONFIG['Packages'][] = array("JSON");
$CONFIG['Packages'][] = array("File", "S3Transport;FileUploader");
$CONFIG['Packages'][] = array("StinglePhotos", "SPKeys");
$CONFIG['Packages'][] = array("Logger", "DBLogger");
$CONFIG['Packages'][] = array("Notifications", "Keybase");