<?php
namespace SPSync\v2;
interface SPDbFileFilter
{
    public function setUserIdEqual($userId);
    public function setUserIdNotEqual($userId);
    public function setFilenameEqual($filename);
    public function setDateCreatedGreater($date);
    public function setDateCreatedGreaterEqual($date);
    public function setDateCreatedLess($date);
    public function setDateCreatedLessEqual($date);
    public function setDateModifiedGreater($date);
    public function setDateModifiedGreaterEqual($date);
    public function setDateModifiedLess($date);
    public function setDateModifiedLessEqual($date);
    
    public function setOrderDateCreatedAsc();
    public function setOrderDateCreatedDesc();
    public function setOrderDateModifiedAsc();
    public function setOrderDateModifiedDesc();
}