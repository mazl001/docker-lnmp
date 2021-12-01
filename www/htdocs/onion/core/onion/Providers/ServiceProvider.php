<?php
namespace Onion\Providers;

use Onion\Container\Application;

abstract class ServiceProvider {
	protected $application;

	public function __construct(Application $application) {
		$this->application = $application;
	}

	/**
	 * 注册服务提供商
	 */
	abstract public function register();
}