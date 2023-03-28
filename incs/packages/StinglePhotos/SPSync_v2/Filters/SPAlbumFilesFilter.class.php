<?php
namespace SPSync\v2;

use Field;
use InvalidArgumentException;
use InvalidIntegerArgumentException;
use MergeableFilter;
use MySqlDatabase;
use Tbl;

class SPAlbumFilesFilter extends MergeableFilter implements SPDbFileFilter{
	
	public function __construct(){
		parent::__construct(Tbl::get('TBL_SP_ALBUM_FILES', 'SPSync\v2\SPSyncManager'), "alb_files", "id");
		
		$this->qb->select(new Field("*", $this->primaryTableAlias))
			->from($this->primaryTable, $this->primaryTableAlias);
	}
    
    public function setAlbumIdEqual($id): SPAlbumFilesFilter {
        if(empty($id)){
            throw new InvalidIntegerArgumentException("\$id shoul be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->equal(new Field('albumId', $this->primaryTableAlias), $id));
        return $this;
    }
	
    public function setFilenameEqual($filename): SPAlbumFilesFilter {
        if(empty($filename)){
            throw new InvalidIntegerArgumentException("\$filename shoul be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->equal(new Field('file', $this->primaryTableAlias), $filename));
        return $this;
    }
    
    public function setUserIdEqual($userId): SPAlbumFilesFilter {
        if(empty($userId) or !is_numeric($userId)){
            throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
        }
        
        $this->joinAlbumsTable();
        $this->qb->andWhere($this->qb->expr()->equal(new Field('userId', 'albums'), $userId));
        return $this;
    }
    
    public function setUserIdNotEqual($userId): SPAlbumFilesFilter {
        if(empty($userId) or !is_numeric($userId)){
            throw new InvalidIntegerArgumentException("\$userId have to be not empty integer");
        }
    
        $this->joinAlbumsTable();
        $this->qb->andWhere($this->qb->expr()->notEqual(new Field('userId', 'albums'), $userId));
        return $this;
    }
    
    public function setIsOwner($val): SPAlbumFilesFilter {
        if(empty($val)){
            throw new InvalidIntegerArgumentException("\$val should be non empty string");
        }
        
        $this->joinAlbumsTable();
        $this->qb->andWhere($this->qb->expr()->equal(new Field('isOwner', 'albums'), $val));
        return $this;
    }
	
	
	public function setDateCreatedGreater($date): SPAlbumFilesFilter {
        if($date === null){
			throw new InvalidArgumentException("\$date have to be non empty string");
		}
		
		$this->qb->andWhere($this->qb->expr()->greater(new Field('dateCreated', $this->primaryTableAlias), $date));
		return $this;
	}
    
    public function setDateCreatedGreaterEqual($date): SPAlbumFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateCreatedLess($date): SPAlbumFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateCreatedLessEqual($date): SPAlbumFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateCreated', $this->primaryTableAlias), $date));
        return $this;
    }
    
    
    public function setDateModifiedGreater($date): SPAlbumFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greater(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedGreaterEqual($date): SPAlbumFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->greaterEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLess($date): SPAlbumFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->less(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
    
    public function setDateModifiedLessEqual($date): SPAlbumFilesFilter {
        if($date === null){
            throw new InvalidArgumentException("\$date have to be non empty string");
        }
        
        $this->qb->andWhere($this->qb->expr()->lessEqual(new Field('dateModified', $this->primaryTableAlias), $date));
        return $this;
    }
	
	
	public function setOrderDateCreatedAsc(): SPAlbumFilesFilter {
		$this->setOrder(new Field('dateCreated', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
        return $this;
	}
    public function setOrderDateCreatedDesc(): SPAlbumFilesFilter{
        $this->setOrder(new Field('dateCreated', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
        return $this;
    }
    
    public function setOrderDateModifiedAsc(): SPAlbumFilesFilter{
        $this->setOrder(new Field('dateModified', $this->primaryTableAlias), MySqlDatabase::ORDER_ASC);
        return $this;
    }
    public function setOrderDateModifiedDesc(): SPAlbumFilesFilter{
        $this->setOrder(new Field('dateModified', $this->primaryTableAlias), MySqlDatabase::ORDER_DESC);
        return $this;
    }
    
    protected function joinAlbumsTable(): SPAlbumFilesFilter{
        $this->qb->leftJoin(Tbl::get('TBL_SP_ALBUMS', 'SPSync\v2\SPSyncManager'),	'albums',
            $this->qb->expr()->equal(new Field('albumId', 'alb_files'), new Field('albumId', 'albums')));
        return $this;
    }
	
}
