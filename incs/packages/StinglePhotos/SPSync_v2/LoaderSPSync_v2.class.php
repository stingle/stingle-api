<?php
class LoaderSPSync_v2 extends Loader{
	protected function includes(){
		stingleInclude ('Exceptions/SPNotEnoughSpaceException.class.php');
		stingleInclude ('Exceptions/SPPermissionCheckFailureException.class.php');
		
		stingleInclude ('Filters/SPDbFileFilter.class.php');
		stingleInclude ('Filters/SPAlbumFilesFilter.class.php');
		stingleInclude ('Filters/SPAlbumsFilter.class.php');
		stingleInclude ('Filters/SPContactsFilter.class.php');
		stingleInclude ('Filters/SPDeletesFilter.class.php');
		stingleInclude ('Filters/SPFilesFilter.class.php');
		stingleInclude ('Filters/SPTrashFilter.class.php');
		
		stingleInclude ('Objects/DeleteFileJobQueueChunk.php');
		stingleInclude ('Objects/SPFile.class.php');
		stingleInclude ('Objects/SPDBFile.class.php');
		stingleInclude ('Objects/SPDBAlbum.class.php');
		stingleInclude ('Objects/SPDBAlbumPermissions.class.php');
		stingleInclude ('Objects/SPDBContact.class.php');
		stingleInclude ('Managers/SPSyncManager.class.php');
	}
	
	protected function customInitBeforeObjects(){
		Tbl::registerTableNames('SPSync\v2\SPSyncManager');
	}
	
	protected function loadSPSyncManager(){
		$this->register(new SPSync\v2\SPSyncManager($this->config->AuxConfig));
	}
	
}
