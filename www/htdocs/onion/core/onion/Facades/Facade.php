<?php
namespace Onion\Facades;

use Onion\Container\Application;

abstract class Facade {
	
	/**
	 * 应用实例
	 */
	protected static $application;


	/**
	 * 该方法需返回Application中注册的标识符
	 */
	abstract public static function getFacadeAccessor();

	/**
	 * 设置应用实例
	 */
	public static function setFacadeApplication(Application $application) {
		self::$application = $application;
	}

	/**
	 * 注册Facade类别名
	 */
	public static function registerClassAlias() {
		spl_autoload_register(function($className) {
			//去除类名的命名空间
			$array = explode('\\', $className);
			$abbrClassName = end($array);

			//类使用时才注册别名，提高性能
			$aliases = self::$application->config['facade.aliases'];
			
			if (isset($aliases[$abbrClassName])) {
				class_alias($aliases[$abbrClassName], $className);
			}
		});
	}
	
	public static function __callStatic($method, $args) {
		$instance = self::$application->make(static::getFacadeAccessor());
		return call_user_func_array([$instance, $method], $args);
	}
}