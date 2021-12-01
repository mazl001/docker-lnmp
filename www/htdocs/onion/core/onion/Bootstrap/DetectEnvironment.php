<?php
namespace Onion\Bootstrap;

use Exception;
use Onion\Container\Application;

class DetectEnvironment {

	/**
	 * 读取.env文件，设置环境变量
	 */
	public function bootstrap(Application $application) {
		$environmentFilePath = $application->getEnvironmentFilePath();
		if (!is_file($environmentFilePath) || !is_readable($environmentFilePath)) {
			throw new Exception("Environment file $environmentFilePath not found or not readable.");	
		}

		//读取.env.json文件, 忽略注释
		$lines = array_filter(file($environmentFilePath), function($line) {
			return strpos(trim($line), '//') !== 0 and strpos(trim($line), '#') !== 0;
		});

		$content = implode("", $lines);
		$env     = json_decode($content, true);

		if (!empty($content) and empty($env)) {
			throw new Exception("Environment file $environmentFilePath doesn’t have a valid JSON format.");
		}

		$_ENV = array_merge($_ENV, $env);
	}
}