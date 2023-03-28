<?php
namespace SPSync\v2;
class SPDBAlbum{
    
    public $userId;
    public $albumId;
	public $encPrivateKey;
	public $publicKey;
    public $metadata;
	public $isShared = 0;
	public $isHidden = 0;
	public $isOwner = 1;
	public $permissions = null;
	public $members = null;
	public $isLocked = 0;
	public $cover = null;
	public $dateCreated;
	public $dateModified;
	
	public SPDBAlbumPermissions $permissionsObj;
	
	public function init($array){
		$this->userId = $array['userId'];
		$this->albumId = $array['albumId'];
		$this->encPrivateKey = $array['encPrivateKey'];
		$this->publicKey = $array['publicKey'];
		$this->metadata = $array['metadata'];
		$this->isShared = $array['isShared'];
		$this->isHidden = $array['isHidden'];
		$this->isOwner = $array['isOwner'];
		$this->permissions = $array['permissions'];
		$this->members = $array['members'];
		$this->isLocked = $array['isLocked'];
		$this->cover = $array['cover'];
		$this->dateCreated = (string)$array['dateCreated'];
		$this->dateModified = (string)$array['dateModified'];
        
        $this->updatePermissionsObj();
	}
	
	public function updatePermissionsObj() {
        if (!empty($this->permissions)) {
           $this->permissionsObj = new SPDBAlbumPermissions($this->permissions);
        }
    }
	
}
