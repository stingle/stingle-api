<?php
namespace SPSync\v2;

use Field;
use InvalidArgumentException;
use InvalidIntegerArgumentException;
use MergeableFilter;
use MySqlDatabase;
use Tbl;

class SPDeletesFilter extends MergeableFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_SP_DELETES', 'SPSync\v2\SPSyncManager'), "dels", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setUserIdEqual($userId): SPDeletesFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setUserIdNotEqual($userId): SPDeletesFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
	
    public function setFilenameEqual($filename): SPDeletesFilter {
        if(empty($filename)){
            throw new InvalidIntegerArgumentException("\$filename shoul be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->equal(new Field('file', $this->primaryTableAlias), $filename));
        return $this;
    }
	
	
	public function setDateGreater($date): SPDeletesFilter {
        if($date === null){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->greater(new Field('date', $this->primaryTableAlias), $date));
		return $this;
	}
    
    public function setDateGreaterEqual($date): SPDeletesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateLess($date): SPDeletesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateLessEqual($date): SPDeletesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('date', $this->primaryTableAlias), $date));
        return $this;
    }
    
    
	public function setOrderDateAsc(): SPDeletesFilter {
		$this->setOrder(new Field('date', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
        return $this;
	}
    public function setOrderDateDesc(){
        $this->setOrder(new Field('date', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
        return $this;
    }
	
}
