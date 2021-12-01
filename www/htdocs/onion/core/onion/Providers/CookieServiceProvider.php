<?php
namespace Onion\Providers;

use Onion\Container\Application;
use Onion\Cookie\CookieManager;

/**
 * 服务提供者: Cookie
 */
class CookieServiceProvider extends ServiceProvider {

	public function register() {
		$this->application->singleton('cookie', function(Application $application) {
			
			$config = $application->config['cookie'];
			
			return new CookieManager($application, $config['lifetime'], $config['cookie_path'], $config['cookie_domain'], $config['cookie_secure'], $config['cookie_httponly']);
		});
	}

	public function boot() {

	}
}