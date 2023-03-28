<?php

use SPSync\v2\SPSyncManager;

isLogined(false);

if(empty($_POST['file'])){
	Reg::get('ao')->setStatusNotOk();
}
if(!isset($_POST['set']) || !in_array($_POST['set'], SPSyncManager::getConstsArray("SET_"))){
	Reg::get('ao')->setStatusNotOk();
}

if(Reg::get('ao')->isStatusOk()){
	try{
		if(Reg::get('spsync')->isFileExistsInDb($_POST['file'], Reg::get('usr'), $_POST['set'])){
			
			header('Content-Description: File Transfer');
			header('Content-Transfer-Encoding: binary');
			header('Pragma: public');
			header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
			header("Content-Disposition: attachment; filename={$_POST['file']}");
			header("Content-Type: " . SPSyncManager::SP_FILE_MIME_TYPE);

			/*$file = Reg::get('spsync')->getFileBody($_POST['file'], (!empty($_POST['thumb'])? true : false));

			header("Content-Length: " . $file['length']);
			echo $file['body'];*/
			$s3Config = ConfigManager::getConfig('File','S3Transport')->AuxConfig->configs->default;
            S3Transport::registerStreamWrapper();
			$cloudPath = Reg::get('spsync')->getFilePathInCloud($_POST['file'], (!empty($_POST['thumb'])? true : false));

			$streamPath = 's3://' . $s3Config->bucket . '/' . $cloudPath;

            header("Content-Length: " . filesize($streamPath));

            $chunkSize = 1024*1024;
            $handle = fopen($streamPath, 'rb');

            if ($handle === false) {
                exit;
            }

            while (!feof($handle)) {
                $buffer = fread($handle, $chunkSize);
                echo $buffer;
                ob_flush();
                flush();
            }

            fclose($handle);
		}
		else{
            Reg::get('ao')->setStatusNotOk();
        }
	}
	catch (Exception $e){
		Reg::get('ao')->setStatusNotOk();
	}
}
exit;