<?php
namespace Onion\Providers;

use Onion\Database\Redis;


/**
 * 服务提供者: Redis
 */
class RedisServiceProvider extends ServiceProvider {
	
	public function register() {
		$this->application->singleton('redis', function() {

			$config = $this->application->config['redis'];
			return new Redis($config);
		});
	}

	public function boot() {

	}
}