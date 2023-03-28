<?php
namespace SPSync\v2;
use RuntimeException;

class SPDBAlbumPermissions{
    
    const PERMISSIONS_VERSION = 1;
    
    const PERM_ALLOW_ADD = 0;
    const PERM_ALLOW_SHARE = 1;
    const PERM_ALLOW_COPY = 2;
    
    public $allowAdd = false;
    public $allowShare = false;
	public $allowCopy = false;
	
	public function __construct($permStr){
		$version = substr($permStr, 0,1);

		if(self::PERMISSIONS_VERSION != $version){
            throw new RuntimeException("Invalid permissions version");
        }

		$addFlag = substr($permStr, 1,1);
		$this->allowAdd = $addFlag == "1";
        
        $shareFlag = substr($permStr, 2,1);
        $this->allowShare = $shareFlag == "1";
        
        $copyFlag = substr($permStr, 3,1);
        $this->allowCopy = $copyFlag == "1";
	}
	
}
