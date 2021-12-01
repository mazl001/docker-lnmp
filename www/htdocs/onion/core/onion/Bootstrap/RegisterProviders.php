<?php
namespace Onion\Bootstrap;

use Onion\Container\Application;

class RegisterProviders {

    /**
     * 注册服务提供者
     */
	public function bootstrap(Application $application) {
		$providers = $application->config['provider'];

		foreach($providers as $provider) {
			$application->registerServiceProvider(new $provider($application));
		}
	}
}