<?php
namespace Onion\Bootstrap;

use Onion\Container\Application;
use Onion\Facades\Facade;

class RegisterFacades {

	/**
	 * 门面Facade设置类别名、Application实例
	 */
	public function bootstrap(Application $application) {
		
		Facade::setFacadeApplication($application);

		Facade::registerClassAlias();
	}
}