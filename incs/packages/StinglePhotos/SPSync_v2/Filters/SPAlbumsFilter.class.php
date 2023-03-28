<?php
namespace SPSync\v2;

use Field;
use InvalidArgumentException;
use InvalidIntegerArgumentException;
use MergeableFilter;
use MySqlDatabase;
use Tbl;

class SPAlbumsFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_SP_ALBUMS', 'SPSync\v2\SPSyncManager'), "albums", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setUserIdEqual($userId): SPAlbumsFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setUserIdNotEqual($userId): SPAlbumsFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
	
    public function setAlbumIdEqual($id): SPAlbumsFilter {
        if(empty($id)){
            throw new InvalidIntegerArgumentException("\$id should be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->equal(new Field('albumId', $this->primaryTableAlias), $id));
        return $this;
    }
    
    public function setIsOwner($val): SPAlbumsFilter {
        if(empty($val)){
            throw new InvalidIntegerArgumentException("\$val should be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->equal(new Field('isOwner', $this->primaryTableAlias), $val));
        return $this;
    }
	
	
	public function setDateCreatedGreater($date): SPAlbumsFilter {
        if($date === null){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->greater(new Field('dateCreated', $this->primaryTableAlias), $date));
		return $this;
	}
    
    public function setDateCreatedGreaterEqual($date): SPAlbumsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateCreatedLess($date): SPAlbumsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateCreatedLessEqual($date): SPAlbumsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    
    public function setDateModifiedGreater($date): SPAlbumsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greater(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedGreaterEqual($date): SPAlbumsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLess($date): SPAlbumsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLessEqual($date): SPAlbumsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
	
	
	public function setOrderDateCreatedAsc(): SPAlbumsFilter {
		$this->setOrder(new Field('dateCreated', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
        return $this;
	}
    public function setOrderDateCreatedDesc(){
        $this->setOrder(new Field('dateCreated', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
        return $this;
    }
    
    public function setOrderDateModifiedAsc(){
        $this->setOrder(new Field('dateModified', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
        return $this;
    }
    public function setOrderDateModifiedDesc(){
        $this->setOrder(new Field('dateModified', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
        return $this;
    }
	
}
