<?php
namespace Onion\Bootstrap;

use Onion\Container\Application;

class BootProviders {

	public function bootstrap(Application $application) {
		$application->bootServiceProvider();
	}
}