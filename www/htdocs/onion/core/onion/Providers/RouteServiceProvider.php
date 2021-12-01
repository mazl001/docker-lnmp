<?php
namespace Onion\Providers;

use Onion\Routing\Router;
use app\Models\User;

/**
 * 服务提供者: 路由
 */
class RouteServiceProvider extends ServiceProvider {

	protected $namespace = 'app\\Http\\Controllers';

	public function register() {

	}

	/**
	 * 加载路由配置文件
	 */
	public function boot(Router $router) {
		//对某个路由参数，设置全局性的正则表达式约束
		//$router->pattern('id', '[0-9]+');

		//路由参数模型绑定
		//$router->model('_user', User::class);

		//获取app目录下各应用名称
		$routesPath = $this->application->getRootPath('routes');
		
		//加载routes目录的路由配置文件(支持子目录)
		$files = _scanDir($routesPath);

		$router->group(['namespace' => $this->namespace], function() use ($files) {
			foreach($files as $file) {
				require $file;
			}
		});
	}
}