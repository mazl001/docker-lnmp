<?php
namespace Onion\Bootstrap;

use Onion\Container\Application;

class LoadConfiguration {
	
	/**
	 * 读取配置文件
	 */
	public function bootstrap(Application $application) {

		$configPath = $application->getConfigFilePath();

		foreach(_scanDir($configPath) as $configFile) {
			$application->config->set(basename($configFile, '.php'), require $configFile);
		}

		//设置默认时区
		date_default_timezone_set($application->config['app.timezone']);
	}
}