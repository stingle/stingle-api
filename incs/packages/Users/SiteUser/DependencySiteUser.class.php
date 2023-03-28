<?php
class DependencySiteUser extends Dependency
{
	public function __construct(){
		$this->addPlugin("Users", "UsersMemcache");
		$this->addPlugin("Db", "Db");
	}
}
