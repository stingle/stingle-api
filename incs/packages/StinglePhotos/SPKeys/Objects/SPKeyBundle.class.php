<?php
class SPKeyBundle{
	
	public $id;
	public $userId;
    public $raw;
    
    public $version;
    public $type;
	public $privateKey;
	public $publicKey;
	public $pwdSalt;
	public $skNonce;
    
    public $serverSK;
    public $serverPK;
	
	public $user = null;
}
