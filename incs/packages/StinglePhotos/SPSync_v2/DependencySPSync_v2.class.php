<?php
class DependencySPSync_v2 extends Dependency
{
	public function __construct(){
		$this->addPlugin("Users", "Users");
		$this->addPlugin("JobQueue", "JobQueue");
	}
}
