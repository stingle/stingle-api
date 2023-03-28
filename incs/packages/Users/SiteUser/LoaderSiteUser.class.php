<?php
class LoaderSiteUser extends Loader{
	protected function includes(){
		stingleInclude ('Filters/SiteUsersFilter.class.php');
		stingleInclude ('Managers/SiteUserManager.class.php');
		stingleInclude ('Managers/SiteUserAuthorization.class.php');
		
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('SiteUserManager');
	}
	
	protected function loadSiteUserManager(){
		$this->register(new SiteUserManager(ConfigManager::getConfig("Users", "Users")->AuxConfig));
	}
	
	protected function loadUserAuthorization(){
		$this->register(new SiteUserAuthorization(ConfigManager::getConfig("Users", "Users")->AuxConfig));
	}
	
}
