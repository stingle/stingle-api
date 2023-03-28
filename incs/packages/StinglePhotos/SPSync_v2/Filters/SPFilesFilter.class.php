<?php
namespace SPSync\v2;

use Field;
use InvalidArgumentException;
use InvalidIntegerArgumentException;
use MergeableFilter;
use MySqlDatabase;
use Tbl;

class SPFilesFilter extends MergeableFilter implements SPDbFileFilter {
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_SP_FILES', 'SPSync\v2\SPSyncManager'), "files", "id");
		
		$this->qb->select(new Field('*', $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setUserIdEqual($userId): SPFilesFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setUserIdNotEqual($userId): SPFilesFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
	
    public function setFilenameEqual($filename): SPFilesFilter {
        if(empty($filename)){
            throw new InvalidIntegerArgumentException("\$filename shoul be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->equal(new Field('file', $this->primaryTableAlias), $filename));
        return $this;
    }
	
	
	public function setDateCreatedGreater($date): SPFilesFilter {
        if($date === null){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->greater(new Field('dateCreated', $this->primaryTableAlias), $date));
		return $this;
	}
    
    public function setDateCreatedGreaterEqual($date): SPFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateCreatedLess($date): SPFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateCreatedLessEqual($date): SPFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    
    public function setDateModifiedGreater($date): SPFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greater(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedGreaterEqual($date): SPFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLess($date): SPFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLessEqual($date): SPFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
	
	
	public function setOrderDateCreatedAsc(): SPFilesFilter {
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
