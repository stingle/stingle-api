<?php
class DependencySPStats extends Dependency
{
	public function __construct(){
		$this->addPlugin("Users", "Users");
	}
}
