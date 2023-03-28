<?php
class LoaderSPStats extends Loader{
	protected function includes(){
		stingleInclude ('Objects/SPStat.class.php');
		stingleInclude ('Managers/SPStatsManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('SPStatsManager');
	}
	
	protected function loadSPStatsManager(){
		$this->register(new SPStatsManager($this->config->AuxConfig));
	}
	
}
