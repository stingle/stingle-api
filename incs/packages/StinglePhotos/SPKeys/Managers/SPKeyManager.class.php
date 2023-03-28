<?php

class SPKeyManager extends DbAccessor {

	const TBL_SP_KEY_BUNDLES = "sp_key_bundles";
	
	const INIT_NONE = 0;
	// Init flags needs to be powers of 2 (1, 2, 4, 8, 16, 32, ...)
	const INIT_USER = 1;
	
	// INIT_ALL Should be next power of 2 minus 1
	const INIT_ALL = 1;
    
    const FILE_BEGGINING = "SPK";
    const FILE_BEGGINING_LEN = 3;
    const FILE_TYPE_LEN = 1;
    const FILE_VERSION_LEN = 1;
    const CURRENT_KEY_FILE_VERSION = 1;
    
    const PUBLIC_KEY_LEN = 32;
    const PRIVATE_KEY_LEN = 32;
    const PRIVATE_KEY_ENC_LEN = 32 + 16;
    const PWSALT_LEN = 16;
    const SK_NONCE_LEN = 24;
    
    const KEY_FILE_TYPE_BUNDLE_ENCRYPTED = 0;
    const KEY_FILE_TYPE_BUNDLE_PLAIN = 1;
    const KEY_FILE_TYPE_PUBLIC_PLAIN = 2;
	
	protected $config;
	
	public function __construct(Config $config, $instanceName = null){
		parent::__construct($instanceName);
		
		$this->config = $config;
	}
	
	public function getKeyBundles(SPKeyBundleFilter $filter = null, MysqlPager $pager = null, $initObjects = self::INIT_NONE, $cacheMinutes = MemcacheWrapper::MEMCACHE_OFF){
		if($filter == null){
			$filter = new SPKeyBundleFilter();
		}
		
		$sqlQuery = $filter->getSQL();
		if($pager !== null){
			$this->query = $pager->executePagedSQL($sqlQuery, $cacheMinutes);
		}
		else{
			$this->query->exec($sqlQuery, $cacheMinutes);
		}
		
		$items = array();
		if($this->query->countRecords()){
			foreach($this->query->fetchRecords() as $row){
				$items[] = $this->getKeyBundleObjectFromData($row, $initObjects);
			}
		}
		return $items;
	}
	
	public function getKeyBundle(SPKeyBundleFilter $filter, $initObjects = self::INIT_NONE){
		$items = $this->getKeyBundles($filter, null, $initObjects);
		if(count($items) !== 1){
			throw new RuntimeException("There is no such user session or it is not unique.");
		}
		return $items[0];
	}
	
	
	public function getKeyBundleByUserId($userId, $initObjects = self::INIT_NONE){
		$filter = new SPKeyBundleFilter();
		$filter->setUserId($userId);
		
		$keyBundle = null;
		
		try{
			$keyBundle = $this->getKeyBundle($filter);
		}
		catch (RuntimeException $e){}
		
		return $keyBundle;
	}
	
	public function insertKeyBundle($userId, $keyBundle){

        $qb = new QueryBuilder();
		$insertArr = array(
			'user_id' => $userId,
			'key_bundle' => $keyBundle,
            'server_keypair' => $this->getNewServerKeyPair()
		);
		
        $qb->insert(Tbl::get("TBL_SP_KEY_BUNDLES"))
            ->values($insertArr);

		return $this->query->exec($qb->getSQL())->affected();
	}
    
    public function updateKeyBundle($userId, $keyBundle){
        
        $qb = new QueryBuilder();
        
        $qb->update(Tbl::get('TBL_SP_KEY_BUNDLES'))
            ->set(new Field('key_bundle'), $keyBundle)
            ->where($qb->expr()->equal(new Field('user_id'), $userId));
        
        return $this->query->exec($qb->getSQL())->affected();
    }
    
    public function deleteKeyBundle($userId){
        
        $qb = new QueryBuilder();
        
        $qb->delete(Tbl::get('TBL_SP_KEY_BUNDLES'))
            ->where($qb->expr()->equal(new Field('user_id'), $userId));
    
        return $this->query->exec($qb->getSQL())->affected();
    }
    
    public function getNewServerKeyPair(){
        $keypair = sodium_crypto_box_keypair();
        $nonce = random_bytes(SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return base64_encode($nonce . sodium_crypto_secretbox($keypair, $nonce, base64_decode($this->config->encryptionKey)));
    }
    
    public function decryptServerKeyPair($keypair){
	    $keypair = base64_decode($keypair);
	    $nonce = substr($keypair, 0, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        $keypair = substr($keypair, SODIUM_CRYPTO_SECRETBOX_NONCEBYTES);
        return sodium_crypto_secretbox_open($keypair, $nonce, base64_decode($this->config->encryptionKey));
    }
    
	public function parseKeyBundle(SPKeyBundle $bundleObj){
	    $bundle = base64_decode($bundleObj->raw);
	    
        $fileBeg = substr($bundle,0, self::FILE_BEGGINING_LEN);
        $bundle = substr($bundle, self::FILE_BEGGINING_LEN);
        
        if($fileBeg !== self::FILE_BEGGINING){
            return null;
        }
        
        $bundleObj->version = ord(substr($bundle,0, self::FILE_VERSION_LEN));
        $bundle = substr($bundle, self::FILE_VERSION_LEN);
        
        if($bundleObj->version > self::CURRENT_KEY_FILE_VERSION){
            return null;
        }
        
        $bundleObj->type = ord(substr($bundle,0, self::FILE_TYPE_LEN));
        $bundle = substr($bundle, self::FILE_TYPE_LEN);
        
        if(!in_array($bundleObj->type, self::getConstsArray("KEY_FILE_TYPE"))){
            return null;
        }
        
        $bundleObj->publicKey = substr($bundle,0, self::PUBLIC_KEY_LEN);
        $bundle = substr($bundle, self::PUBLIC_KEY_LEN);
        
        if($bundleObj->type == self::KEY_FILE_TYPE_BUNDLE_ENCRYPTED){
            $bundleObj->privateKey = substr($bundle,0, self::PRIVATE_KEY_ENC_LEN);
            $bundle = substr($bundle, self::PRIVATE_KEY_ENC_LEN);
    
            $bundleObj->pwdSalt = substr($bundle,0, self::PWSALT_LEN);
            $bundle = substr($bundle, self::PWSALT_LEN);
    
            $bundleObj->skNonce = substr($bundle,0, self::SK_NONCE_LEN);
        }
        elseif($bundleObj->type == self::KEY_FILE_TYPE_BUNDLE_PLAIN){
            $bundleObj->privateKey = substr($bundle,0, self::PRIVATE_KEY_LEN);
        }
        elseif($bundleObj->type == self::KEY_FILE_TYPE_PUBLIC_PLAIN){
            // Already got that
        }
    }
    
    public static function getParamsFromEncMessage($msg, SPKeyBundle $bundle){
	    try {
            $params = base64_decode($msg);
            $nonce = substr($params, 0, SODIUM_CRYPTO_BOX_NONCEBYTES);
            $msg = substr($params, SODIUM_CRYPTO_BOX_NONCEBYTES);
        
            $decMsg = sodium_crypto_box_open($msg, $nonce, sodium_crypto_box_keypair_from_secretkey_and_publickey($bundle->serverSK, $bundle->publicKey));
        
            if (!empty($decMsg)) {
                return json_decode($decMsg);
            }
        }
        catch (Exception $e){
	        return null;
        }
    }
	
	protected function getKeyBundleObjectFromData($data, $initObjects = self::INIT_NONE){
		$key = new SPKeyBundle();
		$key->id 				= $data['id'];
		$key->userId 			= $data['user_id'];
		$key->raw        		= $data['key_bundle'];
		$this->parseKeyBundle($key);
		
		$serverKeypair = $this->decryptServerKeyPair($data['server_keypair']);
        
        $key->serverSK = sodium_crypto_box_secretkey($serverKeypair);
        $key->serverPK = sodium_crypto_box_publickey($serverKeypair);
		
		if (($initObjects & self::INIT_USER) != 0) {
			try{
				$key->user = Reg::get('userMgr')->getUserById($data['user_id']);
			}
			catch(UserNotFoundException $e){ }
		}
		
		return $key;
	}
}
