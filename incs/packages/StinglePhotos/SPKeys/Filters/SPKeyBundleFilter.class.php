<?php
class SPKeyBundleFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_SP_KEY_BUNDLES', 'SPKeyManager'), "spkb", "user_id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setId($id){
		if(empty($id) or !is_numeric($id)){
			throw new InvalidIntegerArgumentException("\$id have to be non zero integer.");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field("id", $this->primaryTableAlias), $id));
		return $this;
	}
	
	public function setUserId($userId){
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be non zero integer.");
		}
	
		$this->qb->andWhere($this->qb->expr()->equal(new Field("user_id", $this->primaryTableAlias), $userId));
		return $this;
	}
	
}
