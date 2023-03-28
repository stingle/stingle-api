<?php

namespace SPSync\v2;

use Config;
use ConfigManager;
use DbAccessor;
use DBLogger;
use Exception;
use FileUploader;
use InvalidArgumentException;
use Reg;
use RuntimeException;
use S3Transport;
use Tbl;
use Field;
use User;
use QueryBuilder;

class SPSyncManager extends DbAccessor
{
    
    const TBL_SP_FILES = "sp_files";
    const TBL_SP_TRASH = "sp_trash";
    const TBL_SP_DELETES = "sp_deletes";
    const TBL_SP_ALBUMS = "sp_albums";
    const TBL_SP_ALBUM_FILES = "sp_album_files";
    const TBL_SP_CONTACTS = "sp_contacts";
    
    const FILE_BEGGINING = "SP";
    
    const FILE_BEGGINING_LEN = 2;
    const FILE_VERSION_LEN = 1;
    const FILE_ID_LEN = 32;
    const HEADER_SIZE_LEN = 4;
    
    const HEADER_BNEGGINING_LEN =
        self::FILE_BEGGINING_LEN +
        self::FILE_VERSION_LEN +
        self::FILE_ID_LEN +
        self::HEADER_SIZE_LEN;
    
    const MAX_HEADER_LENGTH = 1024 * 1024 * 64;
    const MAX_KNOWN_FILE_VERSION = 1;
    
    const SP_FILE_MIME_TYPE = 'application/stinglephoto';
    
    const SET_GALLERY = 0;
    const SET_TRASH = 1;
    const SET_ALBUM = 2;
    const SET_SHARE = 3;
    
    const DELETE_EVENT_GALLERY = 1;
    const DELETE_EVENT_TRASH = 2;
    const DELETE_EVENT_DELETE = 3;
    const DELETE_EVENT_ALBUM = 4;
    const DELETE_EVENT_ALBUM_FILE = 5;
    const DELETE_EVENT_CONTACT = 6;
    
    const INITIAL_FILE_VERSION = 1;
    
    protected $config;
    
    public function __construct(Config $config, $instanceName = null) {
        parent::__construct($instanceName);
        
        $this->config = $config;
    }
    
    public function uploadFile($file, $thumb, User $user, $set = self::SET_GALLERY, $albumId = null, $version = self::INITIAL_FILE_VERSION, $dateCreated = null, $dateModified = null, $headers = null) {
        if (empty($file['name']) || empty($thumb['name'])) {
            throw new InvalidArgumentException("Filename is empty");
        }
        if (empty($file['size']) || empty($thumb['size'])) {
            throw new InvalidArgumentException("File size is empty");
        }
        
        $totalSize = $file['size'] + $thumb['size'];
        
        $this->requireUserToHaveEnoughSpace($user, self::bytesToMb($totalSize), true);
        
        $spFile = $this->parseFile($file["tmp_name"]);
        if ($spFile === null) {
            throw new RuntimeException("Invalid file uploaded");
        }
        
        $spThumb = $this->parseFile($thumb["tmp_name"]);
        if ($spThumb === null) {
            throw new RuntimeException("Invalid thumb uploaded");
        }
        
        if ($spFile->fileId !== $spThumb->fileId) {
            throw new RuntimeException("This thumb is not for this file");
        }
        
        $spFileUploadResult = FileUploader::upload($file, ensureLastSlash($this->config->filesPath) . $file['name']);
        $spThumbUploadResult = FileUploader::upload($thumb, ensureLastSlash($this->config->thumbsPath) . $thumb['name']);
        
        if ($spFileUploadResult && $spThumbUploadResult) {
            $dbFile = new SPDBFile();
            $dbFile->userId = $user->id;
            $dbFile->albumId = $albumId;
            $dbFile->file = $file['name'];
            $dbFile->fileId = $spFile->fileId;
            $dbFile->size = $totalSize;
            $dbFile->version = $version;
            $dbFile->dateCreated = $dateCreated;
            $dbFile->dateModified = getMilliseconds();
            $dbFile->headers = $headers;
            
            $this->addFileToDb($dbFile, $set);
            $this->addUsedSpaceToUser($user, $totalSize, true);
            
        } else {
            throw new RuntimeException("Something went wrong during upload");
        }
    }
    
    public function requireUserToHaveEnoughSpace(User $user, $fileSizeMB, $isMy = true){
        if ($user->props->spaceQuota - $user->props->spaceUsed < $fileSizeMB) {
            if ($isMy) {
                throw new SPNotEnoughSpaceException(C('Not enough free space on your account to add this file'));
            }
            else{
                throw new SPNotEnoughSpaceException(C('Not enough free space on the target account to add this file'));
            }
        }
    }
    
    public function getFileSignedUrl($file) {
        $uploadsPath = ConfigManager::getConfig('File', 'FileUploader')->AuxConfig->S3Config->path;
        
        return S3Transport::getSignedUrl($uploadsPath . ensureLastSlash($this->config->filesPath) . $file, $this->config->defaultUrlExpiration);
    }
    
    public function getThumbSignedUrl($file) {
        $uploadsPath = ConfigManager::getConfig('File', 'FileUploader')->AuxConfig->S3Config->path;
        
        return S3Transport::getSignedUrl($uploadsPath . ensureLastSlash($this->config->thumbsPath) . $file, $this->config->defaultUrlExpiration);
    }
    
    public function getFileBody($file, $isThumb = false) {
        if ($isThumb) {
            return FileUploader::getFileContents(ensureLastSlash($this->config->thumbsPath) . $file);
        } else {
            return FileUploader::getFileContents(ensureLastSlash($this->config->filesPath) . $file);
        }
    }
    
    public function getFilePathInCloud($file, $isThumb = false) {
        $uploadsPath = ConfigManager::getConfig('File', 'FileUploader')->AuxConfig->S3Config->path;
        
        if ($isThumb) {
            return $uploadsPath . ensureLastSlash($this->config->thumbsPath) . $file;
        } else {
            return $uploadsPath . ensureLastSlash($this->config->filesPath) . $file;
        }
    }
    
    public function isFileExistsInDb($fileName, User $user = null, $set = self::SET_GALLERY, $albumId = null, User $excludeUser = null): bool {
        $filter = $this->getFilterBySet($set);
        $filter->setFilenameEqual($fileName);
        
        if(!empty($user)){
            $filter->setUserIdEqual($user->id);
        }
        elseif(!empty($excludeUser)){
            $filter->setUserIdNotEqual($excludeUser->id);
        }
        
        if(!empty($albumId) && $filter instanceof SPAlbumFilesFilter){
            $filter->setAlbumIdEqual($albumId);
        }
    
        $filter->setSelectCount();
    
        $sqlQuery = $filter->getSQL();
    
        $this->query->exec($sqlQuery);
        
        return $this->query->fetchField('cnt') > 0;
    }
    
    public function moveFileFromGalleryToTrash($fileName, User $user) {
        $file = $this->getFileFromDb($fileName, $user, self::SET_GALLERY);
        if (!empty($file)) {
            $file->dateModified = null;
            if (!$this->isFileExistsInDb($fileName, Reg::get('usr'), SPSyncManager::SET_TRASH)) {
               $this->addFileToDb($file, self::SET_TRASH);
            }
            $this->removeFileFromDb($fileName, $user, self::SET_GALLERY);
            $this->addDeleteEventToDb($user->id, $fileName, self::DELETE_EVENT_GALLERY);
        }
        else{
            throw new RuntimeException("File not found");
        }
    }
    
    public function moveFileFromTrashToGallery($fileName, User $user) {
        $file = $this->getFileFromDb($fileName, $user, self::SET_TRASH);
        if (!empty($file)) {
            $file->dateModified = null;
            if (!$this->isFileExistsInDb($fileName, Reg::get('usr'), SPSyncManager::SET_GALLERY)) {
                $this->addFileToDb($file, self::SET_GALLERY);
            }
            $this->removeFileFromDb($fileName, $user, self::SET_TRASH);
            $this->addDeleteEventToDb($user->id, $fileName, self::DELETE_EVENT_TRASH);
        }
        else{
            throw new RuntimeException("File not found");
        }
    }
    
    public function moveFileFromGalleryToAlbum($fileName, User $user, $albumId, $headers, $isMoving = false) {
        $this->checkAlbumPermission($user, $albumId, SPDBAlbumPermissions::PERM_ALLOW_ADD);
    
        $album = $this->getAlbumFromDb($albumId, $user);
        if(empty($album)){
            throw new RuntimeException(C("Album doesn't exist"));
        }
        
        $file = $this->getFileFromDb($fileName, $user, self::SET_GALLERY);
        if (!empty($file)) {
            $file->dateModified = null;
            $file->albumId = $albumId;
            $file->headers = $headers;
            $file->dateModified = null;
            if (!$this->isFileExistsInDb($fileName, Reg::get('usr'), SPSyncManager::SET_ALBUM, $albumId)) {
    
                if(!$album->isOwner) {
                    $owner = $this->getAlbumOwner($albumId);
                    if(!$this->isUserHasFile($fileName, $owner)) {
                        $this->addUsedSpaceToUser($owner, $file->size, false);
                    }
                }
                
                $this->addFileToDb($file, self::SET_ALBUM);
            }
            if ($isMoving) {
                $this->removeFileFromDb($fileName, $user, self::SET_GALLERY);
                $this->addDeleteEventToDb($user->id, $fileName, self::DELETE_EVENT_GALLERY);
                
                if(!$album->isOwner && !$this->isUserHasFile($fileName, $user)){
                    $this->subtractUsedSpaceFromUser($user, $file->size);
                }
            }
        }
        else{
            throw new RuntimeException("File not found");
        }
    }
    
    public function moveFileFromAlbumToGallery($fileName, User $user, $albumId, $headers, $isMoving = false) {
        $this->checkAlbumPermission($user, $albumId, SPDBAlbumPermissions::PERM_ALLOW_COPY);
        
        $file = $this->getFileFromDb($fileName, $user, self::SET_ALBUM, $albumId);
        if (!empty($file)) {
            $file->dateModified = null;
            $file->albumId = null;
            $file->headers = $headers;
            $file->userId = $user->id;
            $file->dateModified = null;
            if (!$this->isFileExistsInDb($fileName, Reg::get('usr'), SPSyncManager::SET_GALLERY)) {
                $album = $this->getAlbumFromDb($albumId, $user);
                if(!empty($album) && !$album->isOwner && !$this->isUserHasFile($fileName, $user)) {
                    $this->addUsedSpaceToUser($user, $file->size, true);
                }
                
                $this->addFileToDb($file, self::SET_GALLERY);
            }
            if ($isMoving) {
                $this->requireSharedAlbumOwner($user, $albumId);
                $this->removeFileFromDb($fileName, $user, self::SET_ALBUM, $albumId);
                $this->addDeleteEventToDb($user->id, $fileName, self::DELETE_EVENT_ALBUM_FILE, $albumId);
            }
        }
        else{
            throw new RuntimeException("File not found");
        }
    }
    
    public function moveFileFromAlbumToAlbum($fileName, User $user, $albumIdFrom, $albumIdTo, $headers, $isMoving = false) {
        $this->checkAlbumPermission($user, $albumIdFrom, SPDBAlbumPermissions::PERM_ALLOW_COPY);
        $this->checkAlbumPermission($user, $albumIdTo, SPDBAlbumPermissions::PERM_ALLOW_ADD);
    
        $albumFrom = $this->getAlbumFromDb($albumIdFrom, $user);
        $albumTo = $this->getAlbumFromDb($albumIdTo, $user);
        
        if(empty($albumFrom) || empty($albumTo)){
            throw new RuntimeException(C("Album doesn't exist"));
        }
    
        $addSpace = false;
        $substractSpace = false;
        // Not from album owner
        if(!$albumFrom->isOwner) {
            if (!$albumTo->isOwner) {
               throw new SPPermissionCheckFailureException(C("Sotty, you can't move directly shared album which is not yours, please copy to your library first."));
            }
            if(!$this->isUserHasFile($fileName, $user)) {
                $addSpace = true;
            }
        }
        
        $file = $this->getFileFromDb($fileName, $user, self::SET_ALBUM, $albumIdFrom);
        if (!empty($file)) {
            $file->dateModified = null;
            $file->albumId = $albumIdTo;
            $file->headers = $headers;
            $file->dateModified = null;
            if (!$this->isFileExistsInDb($fileName, Reg::get('usr'), SPSyncManager::SET_ALBUM, $albumIdTo)) {
                
                if($addSpace) {
                    $this->addUsedSpaceToUser($user, $file->size, true);
                }
                if(!$albumTo->isOwner) {
                    $owner = $this->getAlbumOwner($albumIdTo);
                    if(!$this->isUserHasFile($fileName, $owner)) {
                        $this->addUsedSpaceToUser($owner, $file->size, false);
                    }
                }
                $this->addFileToDb($file, self::SET_ALBUM, $albumIdTo);
            }
            if ($isMoving) {
                $this->requireSharedAlbumOwner($user, $albumIdFrom);
                $this->removeFileFromDb($fileName, $user, self::SET_ALBUM, $albumIdFrom);
                $this->addDeleteEventToDb($user->id, $fileName, self::DELETE_EVENT_ALBUM_FILE, $albumIdFrom);
                if(!$albumTo->isOwner && !$this->isUserHasFile($fileName, $user)){
                    $this->subtractUsedSpaceFromUser($user, $file->size);
                }
            }
        }
        else{
            throw new RuntimeException("File not found");
        }
    }
    
    public function moveFileFromAlbumToTrash($fileName, User $user, $albumId, $headers) {
        $this->requireSharedAlbumOwner($user, $albumId);
        
        $file = $this->getFileFromDb($fileName, $user, self::SET_ALBUM, $albumId);
        if (!empty($file)) {
            $file->dateModified = null;
            $file->albumId = null;
            $file->headers = $headers;
            $file->userId = $user->id;
            $file->dateModified = null;
            if (!$this->isFileExistsInDb($fileName, Reg::get('usr'), SPSyncManager::SET_TRASH)) {
                $this->addFileToDb($file, self::SET_TRASH);
            }
            $this->removeFileFromDb($fileName, $user, self::SET_ALBUM, $albumId);
            $this->addDeleteEventToDb($user->id, $fileName, self::DELETE_EVENT_ALBUM_FILE, $albumId);
        }
        else{
            throw new RuntimeException("File not found");
        }
    }
    
    private array $filesToDelete = array();
    private int $filesDeleteBatchSize = 10;
    public function deleteFile($fileName, User $user, $set = self::SET_TRASH, $force = false) {
        $file = $this->getFileFromDb($fileName, $user, $set);
        
        if (!empty($file)) {
            $existsInUserGallery = $this->isFileExistsInDb($fileName, $user, SPSyncManager::SET_GALLERY);
            $existsInUserAlbums = $this->isFileExistsInUsersOwnAlbums($fileName, $user);
    
            $existsInOthersGallery = $this->isFileExistsInDb($fileName, null, SPSyncManager::SET_GALLERY, null, $user);
            $existsInOthersTrash = $this->isFileExistsInDb($fileName, null, SPSyncManager::SET_TRASH, null, $user);
            $existsInOthersAlbums = $this->isFileExistsInUsersAlbum($fileName, null, null, $user);
    
            if (!$force) {
                $deleteActualFile = !$existsInUserGallery && !$existsInUserAlbums && !$existsInOthersGallery && !$existsInOthersTrash && !$existsInOthersAlbums;
                $subtractUsedSpace = !$existsInUserGallery && !$existsInUserAlbums;
            }
            else{
                $deleteActualFile = !$existsInOthersGallery && !$existsInOthersTrash && !$existsInOthersAlbums;
                $subtractUsedSpace = false;
            }
    
            $this->removeFileFromDb($fileName, $user, $set);
            if ($deleteActualFile) {
                if(count($this->filesToDelete) >= $this->filesDeleteBatchSize){
                    $this->deleteActualFiles();
                }
                array_push($this->filesToDelete, [
                    'filePath' => ensureLastSlash($this->config->filesPath) . $file->file,
                    'thumbPath' => ensureLastSlash($this->config->thumbsPath) . $file->file
                ]);
            }
            
            if(!$force) {
                $this->addDeleteEventToDb($user->id, $fileName, self::DELETE_EVENT_DELETE);
            }
            
            if ($subtractUsedSpace) {
                $this->subtractUsedSpaceFromUser($user, $file->size);
            }
        }
    }
    
    public function deleteActualFiles(){
        if(count($this->filesToDelete)){
            $params = [
                'files' => $this->filesToDelete
            ];
            Reg::get('jobQueue')->addJob(DeleteFileJobQueueChunk::$name, $params);
            $this->filesToDelete = array();
        }
    }
    
    public function isUserHasFile($fileName, User $user){
        $existsInUsersGallery = $this->isFileExistsInDb($fileName, $user, SPSyncManager::SET_GALLERY);
        $existsInUsersTrash = $this->isFileExistsInDb($fileName, $user, SPSyncManager::SET_TRASH);
        $existsInUserAlbums = $this->isFileExistsInUsersOwnAlbums($fileName, $user);
        
        return $existsInUsersGallery || $existsInUsersTrash || $existsInUserAlbums;
    }
    
    
    public function addUsedSpaceToUser(User $user, $bytes, $isMy = true) {
        $this->requireUserToHaveEnoughSpace($user, self::bytesToMb($bytes), $isMy);
        $user->props->spaceUsed += self::bytesToMb($bytes);
        Reg::get('userMgr')->updateUser($user);
    }
    public function subtractUsedSpaceFromUser(User $user, $bytes) {
        $user->props->spaceUsed = $user->props->spaceUsed - self::bytesToMb($bytes);
        if ($user->props->spaceUsed < 0) {
            $user->props->spaceUsed = 0;
        }
        Reg::get('userMgr')->updateUser($user);
    }
    
    public function removeFileFromDb($fileName, User $user, $set = self::SET_GALLERY, $albumId = null) {
        $qb = new QueryBuilder();
    
        $qb->delete(self::getTableNameBySet($set))
            ->where($qb->expr()->equal(new Field('file'), $fileName));
        
        if ($set == self::SET_ALBUM) {
            $qb->andWhere($qb->expr()->equal(new Field('albumId'), $albumId));
        }
        else{
            $qb->andWhere($qb->expr()->equal(new Field('userId'), $user->id));
        }
    
        return $this->query->exec($qb->getSQL())->affected();
    }
    
    
    public function getFileFromDb($fileName, User $user, $set = self::SET_GALLERY, $albumId = null) {
        $filter = $this->getFilterBySet($set);
        $filter->setFilenameEqual($fileName);
        if ($set == self::SET_ALBUM && !empty($albumId)) {
            $filter->setAlbumIdEqual($albumId);
        }
        else{
            $filter->setUserIdEqual($user->id);
        }
        
        $files = $this->getFilesList($filter);
        if(count($files) > 0){
            return $files[0];
        }

        return null;
    }
    
    public function addFileToDb(SPDBFile $file, $set = self::SET_GALLERY, $updateAlbumTime = true) {
        $this->query->lockEndpoint();
    
        $qb = new QueryBuilder();
    
        $data = [
            'file' => $file->file,
            'fileId' => $file->fileId,
            'size' => $file->size,
            'version' => $file->version,
            'dateCreated' => (!empty($file->dateCreated) ? $file->dateCreated : getMilliseconds()),
            'dateModified' => (!empty($file->dateModified) ? $file->dateModified : getMilliseconds()),
            'headers' => $file->headers
        ];
    
        if ($set == self::SET_ALBUM) {
            $data['albumId'] = $file->albumId;
            if ($updateAlbumTime) {
                $this->updateAlbumModifiedTime($file->albumId);
            }
        }
        else {
            $data['userId'] = $file->userId;
        }
        
        if($this->isFileExistsInDb($file->file, Reg::get('userMgr')->getUserById($file->userId), $set, $file->albumId)){
            $qb->update(self::getTableNameBySet($set));
            foreach ($data as $key=>$value){
                $qb->set(new Field($key), $value);
            }
            $qb->where($qb->expr()->equal(new Field('file'), $file->file));
            if ($set == self::SET_ALBUM) {
                $qb->andWhere($qb->expr()->equal(new Field('albumId'), $file->albumId));
            }
            else {
                $qb->andWhere($qb->expr()->equal(new Field('userId'), $file->userId));
            }
        }
        else{
            $qb->insert(self::getTableNameBySet($set))
                ->values($data);
        }
        
        
    
        $this->query->exec($qb->getSQL());
        
        $this->query->unlockEndpoint();
    }
    
    public function getFilterBySet($set) : ?SPDbFileFilter{
        if(!in_array($set, self::getConstsArray("SET"))){
            throw new InvalidArgumentException("Invalid Set specified!");
        }
        
        switch ($set){
            case self::SET_GALLERY:
                return new SPFilesFilter();
            case self::SET_ALBUM:
                return new SPAlbumFilesFilter();
            case self::SET_TRASH:
                return new SPTrashFilter();
        }
    
        return null;
    }
    
    public function getFilesListHelper(User $user, $lastSeenTime = null, $set = self::SET_GALLERY, MysqlPager $pager = null, $cacheMinutes = \MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null) {
        $filter = $this->getFilterBySet($set);
        
        $filter->setUserIdEqual($user->id);
        if ($lastSeenTime !== null) {
            $filter->setDateModifiedGreater($lastSeenTime);
        }
        $filter->setOrderDateModifiedDesc();
        
        return $this->getFilesList($filter, $pager, $cacheMinutes, $cacheTag, false);
    }
    
    /**
     * @return SPDBFile[]
     */
    public function getFilesList(SPDbFileFilter $filter, MysqlPager $pager = null, $cacheMinutes = \MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null, $returnAsObjects = true) : array {
        if(empty($filter)){
            throw new InvalidArgumentException("\$filter can't be empty");
        }
        
        $sqlQuery = $filter->getSQL();
        
        $sql = \MySqlDbManager::getQueryObject();
        if($pager !== null){
            $sql = $pager->executePagedSQL($sqlQuery, $cacheMinutes, null, $cacheTag);
        }
        else{
            $sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
        }
    
    
        $files = array();
        if($sql->countRecords()){
            while(($entry = $sql->fetchRecord()) != false){
                if($returnAsObjects){
                    $file = new SPDBFile();
                    $file->init($entry);
                    array_push($files, $file);
                }
                else{
                    $item = [
                        'file' => $entry['file'],
                        'version' => (isset($entry['version']) ? $entry['version'] : self::INITIAL_FILE_VERSION),
                        'dateCreated' => $entry['dateCreated'],
                        'dateModified' => $entry['dateModified'],
                        'headers' => $entry['headers']
                    ];
                    if($filter instanceof SPAlbumFilesFilter){
                        $item['albumId'] = $entry['albumId'];
                    }
    
                    array_push($files, $item);
                }
    
            }
        }
    
        return $files;
    }
    
    public function getSpaceUsegeByUser(User $user) {
    
    }
    
    public function addDeleteEventToDb($userId, $fileName, $type, $albumId = null, $forAll = true) {
        if (!in_array($type, SPSyncManager::getConstsArray('DELETE_EVENT'))) {
            throw new InvalidArgumentException("Invalid delete event");
        }
        $qb = new QueryBuilder();
        $userIds = [];
        
        if($forAll && !empty($albumId)){
            $userIds = $this->getAlbumParticipants($albumId);
        }
        else{
            $userIds = [$userId];
        }
        
        foreach ($userIds as $uid) {
            $qb->insert(Tbl::get('TBL_SP_DELETES'))
                ->values(array(
                    'userId' => $uid,
                    'file' => $fileName,
                    'type' => $type,
                    'albumId' => $albumId,
                    'date' => getMilliseconds()
                ));
    
            $this->query->exec($qb->getSQL());
        }
    }
    
    public function getDeleteEvents(User $user, $lastSeenTime, MysqlPager $pager = null, $cacheMinutes = \MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null) {
        $filter = new SPDeletesFilter();
        $filter->setUserIdEqual($user->id);
        $filter->setDateGreater($lastSeenTime);
        $filter->setOrderDateDesc();
        
        $sqlQuery = $filter->getSQL();
        $sql = \MySqlDbManager::getQueryObject();
        if($pager !== null){
            $sql = $pager->executePagedSQL($sqlQuery, $cacheMinutes, null, $cacheTag);
        }
        else{
            $sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
        }
    
    
        $return = [];
        if($sql->countRecords()){
            while(($entry = $sql->fetchRecord()) != false){
                $array = [
                    'file' => $entry['file'],
                    'type' => (int)$entry['type'],
                    'date' => (string)$entry['date']
                ];
                if(!empty($entry['albumId'])){
                    $array['albumId'] = $entry['albumId'];
                }
    
                array_push($return, $array);
            }
        }
        
        return $return;
    }
    
    public function removeDeleteEventsFromDb(User $user) {
        $qb = new QueryBuilder();
    
        $qb->delete(Tbl::get('TBL_SP_DELETES'))
            ->where($qb->expr()->equal(new Field('userId'), $user->id));
    
        return $this->query->exec($qb->getSQL())->affected();
    }
    
    
    ///////////////// ALBUMS /////////////////
    
    public function addAlbumToDb(SPDBAlbum $album) {
        $qb = new QueryBuilder();
    
        $qb->insert(Tbl::get('TBL_SP_ALBUMS'))
            ->values(array(
                'userId' => $album->userId,
                'albumId' => $album->albumId,
                'encPrivateKey' => $album->encPrivateKey,
                'publicKey' => $album->publicKey,
                'metadata' => $album->metadata,
                'isShared' => $album->isShared,
                'isHidden' => $album->isHidden,
                'isOwner' => $album->isOwner,
                'permissions' => $album->permissions,
                'members' => $album->members,
                'isLocked' => $album->isLocked,
                'cover' => $album->cover,
                'dateCreated' => (!empty($album->dateCreated) ? $album->dateCreated : getMilliseconds()),
                'dateModified' => (!empty($album->dateModified) ? $album->dateModified : getMilliseconds())
        ));
        
        return $this->query->exec($qb->getSQL())->affected();
    }
    
    public function updateAlbum(SPDBAlbum $album) {
        $qb = new QueryBuilder();
    
        $qb->update(Tbl::get('TBL_SP_ALBUMS'))
            ->set(new Field('encPrivateKey'), $album->encPrivateKey)
            ->set(new Field('publicKey'), $album->publicKey)
            ->set(new Field('metadata'), $album->metadata)
            ->set(new Field('isShared'), $album->isShared)
            ->set(new Field('isHidden'), $album->isHidden)
            ->set(new Field('isOwner'), $album->isOwner)
            ->set(new Field('permissions'), $album->permissions)
            ->set(new Field('members'), $album->members)
            ->set(new Field('isLocked'), $album->isLocked)
            ->set(new Field('cover'), $album->cover)
            ->set(new Field('dateCreated'), (!empty($album->dateCreated) ? $album->dateCreated : getMilliseconds()))
            ->set(new Field('dateModified'), (!empty($album->dateModified) ? $album->dateModified : getMilliseconds()))
            ->where($qb->expr()->equal(new Field('userId'), $album->userId))
            ->andWhere($qb->expr()->equal(new Field('albumId'), $album->albumId));
    
        return $this->query->exec($qb->getSQL())->affected();
    }
    
    public function removeAlbumFromDb($albumId, User $user, $forAll = false) {
        $album = $this->getAlbumFromDb($albumId, $user);
    
        if($forAll && !$album->isOwner){
            throw new SPPermissionCheckFailureException("You can't delete someone else's album");
        }
    
        $this->addDeleteEventToDb($user->id, null, self::DELETE_EVENT_ALBUM, $albumId, $forAll);
    
        $qb = new QueryBuilder();
    
        $qb->delete(Tbl::get('TBL_SP_ALBUMS'))
            ->where($qb->expr()->equal(new Field('albumId'), $albumId));
    
        if (!$forAll) {
            $qb->andWhere($qb->expr()->equal(new Field('userId'), $user->id));
        }
    
        return $this->query->exec($qb->getSQL())->affected();
    }
    
    public function getAlbumsListAfterDate(User $user, $lastSeenTime = null) {
        $filter = new SPAlbumsFilter();
        $filter->setUserIdEqual($user->id);
        if ($lastSeenTime !== null) {
            $filter->setDateModifiedGreater($lastSeenTime);
        }
        
        $filter->setOrderDateModifiedDesc();
        
        $albums = $this->getAlbumsList($filter);
    
        $return = [];
        foreach ($albums as $album) {
            array_push($return, [
                'albumId' => $album->albumId,
                'encPrivateKey' => $album->encPrivateKey,
                'publicKey' => $album->publicKey,
                'metadata' => $album->metadata,
                'isShared' => $album->isShared,
                'isHidden' => $album->isHidden,
                'isOwner' => $album->isOwner,
                'permissions' => $album->permissions,
                'members' => $album->members,
                'isLocked' => $album->isLocked,
                'cover' => $album->cover,
                'dateCreated' => $album->dateCreated,
                'dateModified' => $album->dateModified
            ]);
        }
        
        return $return;
    }
    
    /**
     * @return SPDBAlbum[]
     */
    public function getAlbumsList(SPAlbumsFilter $filter, \MysqlPager $pager = null, $cacheMinutes = \MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null) : array {
        if(empty($filter)){
            throw new InvalidArgumentException("\$filter can't be empty");
        }
    
        $sqlQuery = $filter->getSQL();
        $sql = \MySqlDbManager::getQueryObject();
        if($pager !== null){
            $sql = $pager->executePagedSQL($sqlQuery, $cacheMinutes, null, $cacheTag);
        }
        else{
            $sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
        }
    
    
        $albums = [];
        if($sql->countRecords()){
            while(($entry = $sql->fetchRecord()) != false){
                $album = new SPDBAlbum();
                $album->init($entry);
                array_push($albums, $album);
            }
        }
        return $albums;
    }
    
    public function getAlbumFromDb($albumId, User $user): ?SPDBAlbum {
        $filter = new SPAlbumsFilter();
        $filter->setUserIdEqual($user->id);
        $filter->setAlbumIdEqual($albumId);
    
        $albums = $this->getAlbumsList($filter);
        if(count($albums) === 1){
            return $albums[0];
        }
        return null;
    }
    
    public function getAlbumParticipants($albumId, User $excludeUser = null) : array {
        $filter = new SPAlbumsFilter();
        $filter->setAlbumIdEqual($albumId);
        if(!empty($excludeUser)){
            $filter->setUserIdNotEqual($excludeUser->id);
        }
        
        $albums = $this->getAlbumsList($filter);
    
        $return = [];
        foreach ($albums as $album){
            array_push($return, $album->userId);
        }
        return $return;
    }
    
    public function getAlbumOwner($albumId) : ?User {
        $filter = new SPAlbumsFilter();
        $filter->setAlbumIdEqual($albumId);
        $filter->setIsOwner(1);
        
        $albums = $this->getAlbumsList($filter);
        
        if(count($albums) == 1){
            return Reg::get('userMgr')->getUserById($albums[0]->userId);
        }
        return null;
    }
    
    public function isAlbumExistsInDb($albumId, User $user) : bool {
        $filter = new SPAlbumsFilter();
        $filter->setAlbumIdEqual($albumId);
        $filter->setUserIdEqual($user->id);
    
        $filter->setSelectCount();
    
        $sqlQuery = $filter->getSQL();
    
        $this->query->exec($sqlQuery);
        return $this->query->fetchField('cnt') > 0;
    }
    
    
    
    public function getAlbumFilesByAlbumId($albumId) : array {
        $filter = new SPAlbumFilesFilter();
        $filter->setAlbumIdEqual($albumId);
        
        return $this->getFilesList($filter, null, \MemcacheWrapper::MEMCACHE_OFF, null, false);
    }
    
    public function updateAlbumFilesModifiedTime($albumId) : void {
        $qb = new QueryBuilder();
    
        $qb->update(Tbl::get('TBL_SP_ALBUM_FILES'))
            ->set(new Field('dateModified'), getMilliseconds())
            ->where($qb->expr()->equal(new Field('albumId'), $albumId));
    
    
        $this->query->exec($qb->getSQL())->affected();
    }
    
    public function updateAlbumModifiedTime($albumId) : void {
    
        $qb = new QueryBuilder();
    
        $qb->update(Tbl::get('TBL_SP_ALBUMS'))
            ->set(new Field('dateModified'), getMilliseconds())
            ->where($qb->expr()->equal(new Field('albumId'), $albumId));
    
    
        $this->query->exec($qb->getSQL())->affected();
    }
    
    public function isFileExistsInUsersAlbum($filename, User $user = null, $albumId = null, User $excludeUser = null) : bool {
        $filter = new SPAlbumFilesFilter();
        $filter->setFilenameEqual($filename);
        if(!empty($albumId)){
            $filter->setAlbumIdEqual($albumId);
        }
        if(!empty($user)){
            $filter->setUserIdEqual($user->id);
        }
        elseif(!empty($excludeUser)){
            $filter->setUserIdNotEqual($excludeUser->id);
        }
    
        $filter->setSelectCount();
    
        $sqlQuery = $filter->getSQL();
    
        $this->query->exec($sqlQuery);
        return $this->query->fetchField('cnt') > 0;
    }
    
    public function isFileExistsInUsersOwnAlbums($filename, User $user = null, User $excludeUser = null) : bool {
        $filter = new SPAlbumFilesFilter();
        $filter->setFilenameEqual($filename);
    
        if(!empty($user)){
            $filter->setUserIdEqual($user->id);
        }
        elseif(!empty($excludeUser)){
            $filter->setUserIdNotEqual($excludeUser->id);
        }
        $filter->setIsOwner(1);
        
        $filter->setSelectCount();
    
        $sqlQuery = $filter->getSQL();
    
        $this->query->exec($sqlQuery);
        return $this->query->fetchField('cnt') > 0;
    }
    
    public function checkAlbumPermission(User $user, string $albumId, int $permission) {
        
        $album = $this->getAlbumFromDb($albumId, $user);
        
        if(!empty($album)) {
            if(!$album->isShared){
                return true;
            }
            if (!empty($album->permissionsObj)) {
                
                if($permission == SPDBAlbumPermissions::PERM_ALLOW_ADD && !$album->isOwner && !$album->permissionsObj->allowAdd){
                    throw new SPPermissionCheckFailureException(C("You can't add or delete photos/videos from this album"));
                }
    
                if($permission == SPDBAlbumPermissions::PERM_ALLOW_SHARE && !$album->isOwner && !$album->permissionsObj->allowShare){
                    throw new SPPermissionCheckFailureException(C("You can't share this album"));
                }
                
                if($permission == SPDBAlbumPermissions::PERM_ALLOW_COPY && !$album->isOwner && !$album->permissionsObj->allowCopy){
                    throw new SPPermissionCheckFailureException(C("You can't copy files from this album to your library"));
                }
            }
        }
        return false;
    }
    
    public function requireAlbumOwner(User $user, string $albumId) {
        
        $album = $this->getAlbumFromDb($albumId, $user);
        
        if(!empty($album)) {
            if(!$album->isOwner){
                throw new SPPermissionCheckFailureException(C("For this operation you have to be an album owner"));
            }
        }
    }
    
    public function requireSharedAlbumOwner(User $user, string $albumId) {
        
        $album = $this->getAlbumFromDb($albumId, $user);
        
        if(!empty($album)) {
            if($album->isShared && !$album->isOwner){
                throw new SPPermissionCheckFailureException(C("For this operation you have to be an album owner"));
            }
        }
    }
    
    ///////////////// ALBUMS END /////////////////
    
    ///////////////// CONTACTS /////////////////
    
    public function addContact(SPDBContact $contact) : int {
        $qb = new QueryBuilder();
        $qb->insert(Tbl::get('TBL_SP_CONTACTS'))
            ->values(array(
                'userId' => $contact->userId,
                'friendId' => $contact->friendId,
                'dateUsed' => (!empty($contact->dateUsed) ? $contact->dateUsed : getMilliseconds()),
                'dateModified' => (!empty($contact->dateModified) ? $contact->dateModified : getMilliseconds())
            ));
    
        return $this->query->exec($qb->getSQL())->getLastInsertId();
    }
    
    public function getContactFromDb(User $user1, User $user2) : ?SPDBContact{
        $filter = new SPContactsFilter();
        $filter->setUserIdEqual($user1->id);
        $filter->setFriendIdEqual($user2->id);
        
        $contacts = $this->getContactsList($filter);
        
        if(count($contacts) > 0){
            return $contacts[0];
        }
        return null;
    }
    
    public function insertMutualContacts(User $user1, User $user2) : void {
        $contact = $this->getContactFromDb($user1, $user2);
        if (empty($contact)) {
            $contact = new SPDBContact();
            $contact->userId = $user1->id;
            $contact->friendId = $user2->id;
        }
        $contact->dateUsed = getMilliseconds();
        $contact->dateModified = getMilliseconds();
        $this->addContact($contact);
    
        $contact = $this->getContactFromDb($user2, $user1);
        if (empty($contact)) {
            $contact = new SPDBContact();
            $contact->userId = $user2->id;
            $contact->friendId = $user1->id;
        }
        $contact->dateUsed = getMilliseconds();
        $contact->dateModified = getMilliseconds();
        $this->addContact($contact);
    }
    
    public function getContactsListHelper(User $user, $lastSeenTime = null) : array {
        $filter = new SPContactsFilter();
        $filter->setUserIdEqual($user->id);
        if ($lastSeenTime !== null) {
            $filter->setDateModifiedGreater($lastSeenTime);
        }
        $filter->setOrderDateModifiedDesc();
        
        return $this->getContactsList($filter, null, \MemcacheWrapper::MEMCACHE_OFF, null, false);
    }
    
    /**
     * @return SPDBContact[]
     */
    public function getContactsList(SPContactsFilter $filter, MysqlPager $pager = null, $cacheMinutes = \MemcacheWrapper::MEMCACHE_OFF, $cacheTag = null, $returnAsObjects = true) : array {
        if(empty($filter)){
            throw new InvalidArgumentException("\$filter can't be empty");
        }
    
        $sqlQuery = $filter->getSQL();
        $sql = \MySqlDbManager::getQueryObject();
        if($pager !== null){
            $sql = $pager->executePagedSQL($sqlQuery, $cacheMinutes, null, $cacheTag);
        }
        else{
            $sql->exec($sqlQuery, $cacheMinutes, $cacheTag);
        }
    
    
        $return = array();
        if($sql->countRecords()){
            while(($entry = $sql->fetchRecord()) != false){
                if($returnAsObjects){
                    $item = new SPDBContact();
                    $item->init($entry);
                    array_push($return, $item);
                }
                else{
                    $friend = Reg::get('userMgr')->getUserById($entry['friendId']);
                    if(empty($friend)){
                        continue;
                    }
                    $friendKeyBundle = Reg::get('spkeys')->getKeyBundleByUserId($friend->id);
                    if(empty($friendKeyBundle)){
                        continue;
                    }
                    array_push($return, [
                        'userId' => $friend->id,
                        'email' => $friend->email,
                        'publicKey' => base64_encode($friendKeyBundle->publicKey),
                        'dateUsed' => (string)$entry['dateUsed'],
                        'dateModified' => (string)$entry['dateModified']
                    ]);
                }
            
            }
        }
    
        return $return;
    }
    
    public function removeUserFromEverybodyContacts(User $user) : int {
        $filter = new SPContactsFilter();
        $filter->setFriendIdEqual($user->id);
        
        $contacts = $this->getContactsList($filter);
        foreach ($contacts as $contact){
            $this->addDeleteEventToDb($contact->userId, $user->id, self::DELETE_EVENT_CONTACT, null, false);
        }
        
        $qb = new QueryBuilder();
        $qb->delete(Tbl::get('TBL_SP_CONTACTS'))
            ->where($qb->expr()->equal(new Field('friendId'), $user->id))
            ->orWhere($qb->expr()->equal(new Field('userId'), $user->id));
    
        return $this->query->exec($qb->getSQL())->affected();
    }
    
    ///////////////// CONTACTS END /////////////////
    
    
    public function getTotalUsedSpaceForUser(User $user) {
        return $this->getTotalUsedSpaceForUserBySet($user, self::SET_GALLERY) +
            $this->getTotalUsedSpaceForUserBySet($user, self::SET_TRASH) +
            $this->getTotalUsedSpaceForUserBySet($user, self::SET_ALBUM);
    }
    
    public function getTotalUsedSpaceForUserBySet(User $user, $set) : int {
        $filter = $this->getFilterBySet($set);
        $filter->setUserIdEqual($user->id);
        
        $qb = $filter->getQb();
        $qb->select(new \Func('SUM', new Field('size'), 'sizeSum'));
    
        $sqlQuery = $qb->getSQL();
        $sql = \MySqlDbManager::getQueryObject();
        $sql->exec($sqlQuery);
    
    
        $files = array();
        if($sql->countRecords()) {
            $sum = $sql->fetchField('sizeSum');
            if(empty($sum)){
                $sum = 0;
            }
            return $sum;
        }
        return 0;
    }
    
    public function parseFile($filePath): ?SPFile {
        $fd = fopen($filePath, 'rb');
        if ($fd === false) {
            return false;
        }
        
        $spFile = new SPFile();
        
        $fileBeginning = fread($fd, self::FILE_BEGGINING_LEN);
        
        if ($fileBeginning !== self::FILE_BEGGINING) {
            return null;
        }
        
        $fileVersion = ord(fread($fd, self::FILE_VERSION_LEN));
        
        if ($fileVersion < 1 || $fileVersion > self::MAX_KNOWN_FILE_VERSION) {
            return null;
        }
        
        $fileId = base64_url_encode(fread($fd, self::FILE_ID_LEN));
        $headerSize = self::byteArrayToInt(fread($fd, self::HEADER_SIZE_LEN));
        
        if ($headerSize >= self::MAX_HEADER_LENGTH) {
            return null;
        }
        $encHeader = base64_url_encode(fread($fd, $headerSize));
        
        $spFile->fileVersion = $fileVersion;
        $spFile->fileId = $fileId;
        $spFile->headerSize = $headerSize;
        $spFile->encHeader = $encHeader;
        $spFile->overallHeaderSize = self::HEADER_BNEGGINING_LEN + $spFile->headerSize;
        
        return $spFile;
    }
    
    public static function getRemoteFileSize($url) {
        echo "$url\n";
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_NOBODY, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        
        $data = curl_exec($ch);
        curl_close($ch);
        
        if (preg_match('/Content-Length: (\d+)/', $data, $matches)) {
            return (int)$matches[1];
        }
        return -1;
    }
    
    private static function byteArrayToInt($b) {
        return (ord($b[3]) & 0xFF) + ((ord($b[2]) & 0xFF) << 8) + ((ord($b[1]) & 0xFF) << 16) + ((ord($b[0]) & 0xFF) << 24);
    }
    
    private static function intToByteArray($int) {
        return
            chr($int >> 24 & 0xFF) .
            chr($int >> 16 & 0xFF) .
            chr($int >> 8 & 0xFF) .
            chr($int >> 0 & 0xFF);
    }
    
    protected static function getTableNameBySet($set) {
        if (!in_array($set, SPSyncManager::getConstsArray('SET_'))) {
            throw new InvalidArgumentException("Invalid set");
        }
        if ($set == self::SET_GALLERY) {
            return Tbl::get("TBL_SP_FILES");
        } elseif ($set == self::SET_TRASH) {
            return Tbl::get("TBL_SP_TRASH");
        } elseif ($set == self::SET_ALBUM) {
            return Tbl::get("TBL_SP_ALBUM_FILES");
        }
    }
    
    public static function bytesToMb($bytes) {
        return round($bytes / (1024 * 1024));
    }
}
