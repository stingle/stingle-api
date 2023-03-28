<?php
class DependencySPKeys extends Dependency
{
	public function __construct(){
		$this->addPlugin("Users", "Users");
	}
}
