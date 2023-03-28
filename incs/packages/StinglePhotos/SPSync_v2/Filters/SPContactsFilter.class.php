<?php
namespace SPSync\v2;
use Field;
use InvalidArgumentException;
use InvalidIntegerArgumentException;
use MergeableFilter;
use MySqlDatabase;
use Tbl;

class SPContactsFilter extends MergeableFilter{
    
    public function __construct(){
		parent::__construct(Tbl::get('TBL_SP_CONTACTS', 'SPSync\v2\SPSyncManager'), "contacts", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
	
	public function setUserIdEqual($userId): SPContactsFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
		
		$this->qb->andWhere($this->qb->expr()->equal(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
	
	public function setUserIdNotEqual($userId): SPContactsFilter {
		if(empty($userId) or !is_numeric($userId)){
			throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
		}
	
		$this->qb->andWhere($this->qb->expr()->notEqual(new Field('userId', $this->primaryTableAlias), $userId));
		return $this;
	}
    
    public function setFriendIdEqual($userId): SPContactsFilter {
        if(empty($userId) or !is_numeric($userId)){
            throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
        }
        
        $this->qb->andWhere($this->qb->expr()->equal(new Field('friendId', $this->primaryTableAlias), $userId));
        return $this;
    }
    
    public function setFriendIdNotEqual($userId): SPContactsFilter {
        if(empty($userId) or !is_numeric($userId)){
            throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
        }
        
        $this->qb->andWhere($this->qb->expr()->notEqual(new Field('friendId', $this->primaryTableAlias), $userId));
        return $this;
    }
	
	
	public function setDateUsedGreater($date): SPContactsFilter {
        if($date === null){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->greater(new Field('dateUsed', $this->primaryTableAlias), $date));
		return $this;
	}
    
    public function setDateUsedGreaterEqual($date): SPContactsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateUsed', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateUsedLess($date): SPContactsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateUsed', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateUsedLessEqual($date): SPContactsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateUsed', $this->primaryTableAlias), $date));
        return $this;
    }
    
    
    public function setDateModifiedGreater($date): SPContactsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greater(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedGreaterEqual($date): SPContactsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLess($date): SPContactsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLessEqual($date): SPContactsFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
	
	
	public function setOrderDateUsedAsc(): SPContactsFilter {
		$this->setOrder(new Field('dateUsed', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
        return $this;
	}
    public function setOrderDateUsedDesc(): SPContactsFilter{
        $this->setOrder(new Field('dateUsed', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
        return $this;
    }
    
    public function setOrderDateModifiedAsc(): SPContactsFilter{
        $this->setOrder(new Field('dateModified', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
        return $this;
    }
    public function setOrderDateModifiedDesc(): SPContactsFilter{
        $this->setOrder(new Field('dateModified', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
        return $this;
    }
	
}
