<?php
class LoaderUserValidation extends Loader{
	protected function includes(){
		stingleInclude ('Managers/UserValidation.class.php');
	}
	
	protected function loadUserValidation(){
		$this->register(new UserValidation());
	}
}
