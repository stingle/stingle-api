<?php
class DependencyResend extends Dependency
{
	public function __construct(){
		$this->addPlugin("Mail", "Mail");
	}
}
