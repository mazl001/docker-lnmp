<?php
namespace Onion\Middleware;

use Closure;
use Exception;
use Onion\Container\Application;
use Onion\Http\Request;

/**
 * 中间件: 检测应用是否在维护
 */
class CheckForMaintenanceMode {

	public function __construct(Application $application) {
		$this->application = $application;
	}

	public function handle(Request $request, Closure $next) {
		
		if($this->application->config['app.downForMaintenance']) {
			die($this->application->config['app.maintenanceTips']);
		}

		return $next($request);
	}
}