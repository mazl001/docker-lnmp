<?php
namespace Onion\Providers;

use Onion\Container\Application;
use Jenssegers\Blade\Blade;

/**
 * 服务提供者: 视图
 */
class ViewServiceProvider extends ServiceProvider {

	public function register() {
		$this->application->singleton('view', function(Application $application) {

			$paths = $application->config['view.paths'];
			$cache = $application->config['view.compiled'];

			$blade = new Blade($paths, $cache);

			return $blade;	
		});
	}

	public function boot() {

	}
}