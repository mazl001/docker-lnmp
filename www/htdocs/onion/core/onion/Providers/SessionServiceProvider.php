<?php
namespace Onion\Providers;

use Onion\Container\Application;
use Onion\Session\SessionManager;

/**
 * 服务提供者: Session
 */
class SessionServiceProvider extends ServiceProvider {

	public function register() {
		$this->application->singleton('session', function(Application $application) {
			return new SessionManager($application);
		});
	}

	public function boot() {

	}
}