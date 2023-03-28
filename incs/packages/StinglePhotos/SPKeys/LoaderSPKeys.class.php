<?php
class LoaderSPKeys extends Loader{
	protected function includes(){
		stingleInclude ('Filters/SPKeyBundleFilter.class.php');
		stingleInclude ('Objects/SPKeyBundle.class.php');
		stingleInclude ('Managers/SPKeyManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('SPKeyManager');
	}
	
	protected function loadSPKeyManager(){
		$this->register(new SPKeyManager($this->config->AuxConfig));
	}
	
}
