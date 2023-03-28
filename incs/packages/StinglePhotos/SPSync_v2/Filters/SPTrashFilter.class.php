<?php
namespace SPSync\v2;

use Field;
use InvalidArgumentException;
use InvalidIntegerArgumentException;
use MergeableFilter;
use MySqlDatabase;
use Tbl;

class SPTrashFilter extends MergeableFilter implements SPDbFileFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_SP_TRASH', 'SPSync\v2\SPSyncManager'), "trash", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setUserIdEqual($userId): SPTrashFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setUserIdNotEqual($userId): SPTrashFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
	
    public function setFilenameEqual($filename): SPTrashFilter {
        if(empty($filename)){
            throw new InvalidIntegerArgumentException("\$filename shoul be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->equal(new Field('file', $this->primaryTableAlias), $filename));
        return $this;
    }
	
	
	public function setDateCreatedGreater($date): SPTrashFilter {
        if($date === null){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->greater(new Field('dateCreated', $this->primaryTableAlias), $date));
		return $this;
	}
    
    public function setDateCreatedGreaterEqual($date): SPTrashFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateCreatedLess($date): SPTrashFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateCreatedLessEqual($date): SPTrashFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    
    public function setDateModifiedGreater($date): SPTrashFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greater(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedGreaterEqual($date): SPTrashFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLess($date): SPTrashFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLessEqual($date): SPTrashFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
	
	
	public function setOrderDateCreatedAsc(): SPTrashFilter {
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
