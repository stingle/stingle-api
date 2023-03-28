<?php
namespace SPSync\v2;
class SPDBFile{
	
	public $userId;
	public $albumId = null;
	public $file;
	public $fileId;
	public $size;
	public $version = SPSyncManager::INITIAL_FILE_VERSION;
	public $dateCreated = null;
	public $dateModified = null;
	public $headers = null;
	
	public function init($array){
		$this->userId = (isset($array['userId']) ? $array['userId'] : null);
        $this->albumId = (isset($array['albumId']) ? $array['albumId'] : null);
		$this->file = $array['file'];
		$this->fileId = $array['fileId'];
		$this->size = $array['size'];
		$this->version = (isset($array['version']) ? $array['version'] : SPSyncManager::INITIAL_FILE_VERSION);
		$this->dateCreated = (string)$array['dateCreated'];
		$this->dateModified = (string)$array['dateModified'];
		$this->headers = (string)$array['headers'];
	}
}
