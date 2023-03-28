<?php
/**
 * Class for user Validation
 */
class UserValidation{

	private $errors = array();

	public function __construct(){
		
	}
	
	public function checkEmail($email){
		if(empty($email)){
			$this->addError(C("Email address can't be empty"));
			return false;
		}
		
		$filter = new UsersFilter();
		$filter->setLogin($email);
		if(Reg::get('userMgr')->getUsersListCount($filter)){
			$this->addError(C('Sorry this mail is already in use'));
			return false;
		}
		
		$filter = new UsersFilter();
		$filter->setEmail($email);
		if(Reg::get('userMgr')->getUsersListCount($filter)){
			$this->addError(C('Sorry this mail is already in use'));
			return false;
		}
		
		$cachedResult = Reg::get('memcache')->getObject('validEmail', md5($email));
		if($cachedResult == 'v'){
			return true;
		}
		elseif($cachedResult == 'i'){
			$this->addError(C('Email address provided is not valid'));
			return false;
		}
		
		$result = false;

		if(empty($email)){
			$this->addError(C('Please enter valid email address'));
		}
		elseif(!valid_email($email)){
			$this->addError(C('Email address provided is not valid'));
		}
		elseif(!HookManager::callBooleanAndHook('ValidateEmail', $email)){
			$this->addError(C('Email address provided is not valid'));
		}
		else{
			$result = true;
		}
		
		Reg::get('memcache')->setObject('validEmail', md5($email), ($result == true ? 'v' : 'i'));
		
		return $result;
	}
	
	/**
	 * Check password Requirements
	 * @param String $password
	 */
	public function checkPassword($password){
		if(strlen($password) < 6){
			$this->addError(C('Password should be more than 6 characters long'));
		}
	}
	
	public function checkSalt($salt){
		if(empty($salt)){
			$this->addError(C('Invalid salt'));
		}
	}
    
    public function checkKeyBundle($bundle){
        if(empty($bundle)){
            $this->addError(C('Invalid key bundle'));
        }
    }
	
	public function hasError(){
		if (empty($this->errors)){
			return false;
		}
		return true;
	}

	public function getErrors(){
		return $this->errors;
	}

	private function addError($errorConst){
		array_push($this->errors,$errorConst);
	}
	
}
