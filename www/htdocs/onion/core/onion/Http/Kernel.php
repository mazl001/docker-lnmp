<?php
namespace Onion\Http;

use Closure;
use ReflectionClass;
use Onion\Container\Application;
use Onion\Http\Request;
use Onion\Routing\RouteItem;


class Kernel {

	/**
	 * Application实例
	 */
	protected $application;

	/**
	 * 构造方法
	 */
	public function __construct(Application $application) {
		$this->application = $application;
	}

	/**
	 * 处理请求
	 */
	public function handle(Request $request) {
		//应用初始化
		$this->application->bootstrap();

		//匹配路由
		$route = $this->application->router->findRoute($request);

		//收集所有的中间件
		$middlewares = $this->gatherMiddlewares($route);

		foreach($middlewares as $key => $middleware) {
			$middlewares[$key] = function($next) use ($middleware) {
									return function($request) use($next, $middleware) {
										return $this->runMiddleware($middleware, $request, $next);
									};
								 };
		}

		//封装成 $onion = m1(m2(m3($this->dispatchToRoute($route)))) 的形式
		$onion = array_reduce(
					array_reverse($middlewares), 
					function($carry, $item) {
						return $item($carry);
					},
					$this->dispatchToRoute($route)
				);

		return $onion($request);
	}

	public function dispatchToRoute(RouteItem $route) {
		return function($request) use ($route) {
			return $route->run($request, $this->application);
		};
	}

	/**
	 * 运行中间件
	 * @param string  $middleware 中间件名称与参数, 格式: middleware:param1,param2,param3
	 * @param Request $request    请求对象
	 * @param Closure $next       匿名函数, 运行下一个中间件
	 */
	public function runMiddleware($middleware, $request, $next) {
		$parameters = [$request, $next];

		//解析中间件参数
		if (strpos($middleware, ':') !== false) {
			list($middleware, $middlewareParameters) = explode(':', $middleware);
			$parameters = array_merge($parameters, explode(',', $middlewareParameters));
		}

		$instance = $this->application->getNewInstanceByClassName($middleware);
	    return $this->application->invokeMethod($instance, 'handle', $parameters);
	}


	/**
	 * 收集所有的中间件
	 * @param RouteItem  $route  匹配到的路由对象
	 */
	public function gatherMiddlewares(RouteItem $route) {

		//获取全局中间件
		$middlewares = $this->application->config['middleware.global'];

		//获取路由中指定的中间件
		$middlewares = array_merge($middlewares, $route->getMiddleware());

		//获取controller的middleware属性指定的中间件
		$action = $route->getAction();
		if(is_string($action['uses'])) {
			list($class, $method) = explode('@', $action['uses']);
			$middlewares = array_merge($middlewares, $this->getControllerMiddlewares($class));
		}

		//根据中间件键名，获取中间件真实类名 (作用于: 路由中间件、控制器中间件)
		$routeMiddlewares = $this->application->config['middleware.route'];
		array_walk($middlewares, function(&$middleware) use ($routeMiddlewares) {
			list($key) = explode(':', $middleware);

			if (array_key_exists($key, $routeMiddlewares)) {
				$middleware = substr_replace($middleware, $routeMiddlewares[$key], 0, strlen($key));
			}
		});

		return $middlewares;
	} 

	/**
	 * 获取控制器定义的中间件
	 * @param string  $controllerName  控制器类名
	 */	
	public function getControllerMiddlewares($controllerName) {
		$reflectClass = new ReflectionClass($controllerName);
		
		if ($reflectClass->hasProperty('middleware')) {
            $reflectionProperty = $reflectClass->getProperty('middleware');
            $reflectionProperty->setAccessible(true);

            $controllerMiddlewares = $reflectionProperty->getValue($this->application->make($controllerName));
		}

		return $controllerMiddlewares ?? [];
	}
}