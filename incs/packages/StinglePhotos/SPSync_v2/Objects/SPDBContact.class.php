<?php
namespace SPSync\v2;
class SPDBContact{
    
    public $userId;
    public $friendId;
	public $dateUsed;
	public $dateModified;
	
	public function init($array){
		$this->userId = $array['userId'];
		$this->friendId = $array['friendId'];
		$this->dateUsed = (string)$array['dateUsed'];
		$this->dateModified = (string)$array['dateModified'];
	}
}
