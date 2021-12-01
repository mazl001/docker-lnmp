<?php
namespace Onion\Routing;

use Closure;
use Exception;
use Onion\Http\Request;
use Onion\Container\Application;


/**
 * 路由管理类
 */
class Router {

	/**
	 * 已注册的路由对象
	 */
	protected $routes = [];

	/**
	 * 路由名称 => 路由对象
	 */
	protected $nameList = [];

	/**
	 * 已注册的路由模型绑定
	 */
	protected $binders = [];

	/**
	 * 全局性的正则表达式约束
	 */
	protected $patterns = [];

	/**
	 * 路由分组属性栈（先进后出）
	 */
	protected $groupStack = [];

	/**
	 * 路由分组可设置的公共属性
	 */
	protected $validGroupOptions = ['namespace', 'prefix', 'name', 'domain',  
	'middleware', 'http', 'https'];

	/**
	 * 路由可设置的属性 不支持设置namespace、prefix
	 */
	protected $validActionOptions = ['name', 'domain', 'middleware', 'http', 'https'];

	/**
	 * 支持的请求方法
	 */
	protected $verbs = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'];

	/**
	 * 构造函数
	 */
	public function __construct(Application $application) {
		$this->application = $application;
	}

	/**
	 * 路由分组
	 * @param array $attributes 路由分组公共属性. 分组嵌套时，子分组会继承父分组的属性
	 */
	public function group(array $attributes, Closure $callback) {
		
		$this->updateGroupStack($attributes);

		call_user_func($callback);

		array_pop($this->groupStack);
	}

	/**
	 * 注册GET请求路由
	 */
	public function get($uri, $action) {
		return $this->addRoute('GET', $uri, $action);
	}

	/**
	 * 注册POST请求路由
	 */
	public function post($uri, $action) {
		return $this->addRoute('POST', $uri, $action);
	}

	/**
	 * 注册PUT请求路由
	 */
	public function put($uri, $action) {
		return $this->addRoute('PUT', $uri, $action);
	}

	/**
	 * 注册PATCH请求路由
	 */
	public function patch($uri, $action) {
		return $this->addRoute('PATCH', $uri, $action);
	}

	/**
	 * 注册DELETE请求路由
	 */
	public function delete($uri, $action) {
		return $this->addRoute('DELETE', $uri, $action);
	}

	/**
	 * 注册请求路由，响应多个HTTP请求方法
	 */
	public function match($methods, $uri, $action) {
		return $this->addRoute(array_map('strtoupper', (array)$methods), $uri, $action);
	}

	/**
	 * 注册请求路由，响应所有HTTP请求方法
	 */
	public function any($uri, $action) {
		return $this->addRoute($this->verbs, $uri, $action);
	}

	/**
	 * 注册miss路由
	 */
	public function miss($action) {
		return $this->addRoute($this->verbs, '404NotFound', $action)->name('404');
	}

    /*
    |--------------------------------------------------------------------------
    | 添加路由规则
    |--------------------------------------------------------------------------
    | 例如有以下路由地址:
    |
	| 1、路由地址: user/profile
    | 2、路由地址: user/{id}
	| 3、路由地址: posts/{postId}/comments/{commentId}
	| 
	| 路由存储数组的数据结构如下, 优点: 搜索路由时不再需要验证所有的路由对象
    |
	| $route['GET'] = [
	|		'user' => [
    |			'profile' => [
	|				'_route'			=>	new Route('user/profile'),	
    |			],
	|
    |			'{param}' => [
	|				'_route'			=>	new Route('user/{id}'),
    |			],
    |
	|		'posts'	  => [
	|			'{param}'	=>	[
	|				'comments'	=>	[
	|					'{param}'	=>	[
	|						'_route'	=>	new Route('posts/{postId}/comments/{commentId}'),
	|					],
	|				]
	|			]
	|		],
	| ];
	|
	| 常见的路由规则, 使用1维数组进行存储, 缺点: 搜索时, 需要遍历验证所有的路由对象
 	|
	| $route['GET'] = [new Route('user/profile'), new Route('user/{id}')...]
    */
	public function addRoute($methods, $uri, $action) {
		$route = $this->createRoute($methods, $uri, $action);
		$paths = $route->getUri() !== '/' ? explode('/', $route->getUri()) : ['/'];

		foreach (array_reverse($paths) as $path) {
			
			if (substr($path, 0, 1) === '{' and substr($path, -1, 1) === '}') {
				$path = '{param}';
			}

			$result = [$path => $result ?? ['_route' => $route]];
		}

		foreach($route->getMethods() as $method) {
			$this->routes = array_merge_recursive($this->routes, [$method => $result]);
		}

		return $route;
	}

    /*
    |--------------------------------------------------------------------------
    | 搜索与请求匹配的路由对象
    |--------------------------------------------------------------------------
    | 例如有以下GET请求:
    | 
    | 1、请求地址: user/profile 
    |	搜索范围: $routes['GET']['user']['profile']
	|
	| 2、请求地址: user/{id}
	|   搜索范围: $routes['GET']['user']
    */
	public function findRoute(Request $request) {
		$routes = $this->routes[$request->getMethod()];
		$paths  = $request->getPath() !== '/' ? explode('/', $request->getPath()) : ['/'];

		//缩小搜索范围
		while($path = array_shift($paths)) {
			
			if (!isset($routes[$path])) {
				break;
			}

			$routes = $routes[$path];
		}

		//遍历数组搜索与请求匹配的路由对象
		function arrayIteratorRecursive($array, $request, &$route) {
			foreach ($array as $key => $value) {

				if ($key == '_route' and $value->matches($request)) {
					$route = $value;
				}

				if (is_array($value)) arrayIteratorRecursive($value, $request, $route);
			}
		}

		$route = null;
		arrayIteratorRecursive($routes, $request, $route);

		if (isset($route) or isset($this->nameList['404'])) {
			return $route ?? $this->nameList['404'];
		}

		throw new Exception('No route was found');
	}

    /*
    |--------------------------------------------------------------------------
    | 通过名称搜素路由
    |--------------------------------------------------------------------------
    */
	public function findRouteByName($name) {
		return $this->nameList[$name] ?? null;
	}

	/**
	 * 创建路由规则实例
	 */
	public function createRoute($methods, $uri, $action) {

		//如果action是字符串、闭包形式，封装成数组
		if (is_string($action) or $action instanceof Closure) {
			$action = ['uses' => $action];
		}

		//如果action是数组，且不含键值'uses'，从数组中自动寻找可调用的对象
		if (is_array($action) and !isset($action['uses'])) {
			$action['uses'] = $this->findCallable($action);
		}

		$route = new RouteItem($methods, $uri, ['uses' => $action['uses']]);

		//用路由分组公共属性 设置 $route
		if (!empty($this->groupStack)) {
			$this->updateRouteAttributes($route, end($this->groupStack),$this->validGroupOptions);
		}

		//用路由属性 再次设置 $route
		$this->updateRouteAttributes($route, $action, $this->validActionOptions);

		return $route;
	}

	/**
	 * 记录: 路由名称 => 路由对象
	 */
	public function updateNameList(string $name, $route) {
		$this->nameList[$name] = $route;
		$this->nameList = array_filter($this->nameList);
	}

	/**
	 * 更新路由属性
	 */
	public function updateRouteAttributes($route, $attributes, $validOptions = []) {
		foreach($attributes as $key => $value) {
			//设置键值对属性, 如：['prefix' => 'admin']
			if (in_array($key, $validOptions, true)) {
				$route->$key($value);
			};

			//设置非键值对属性，如：['http', 'https']
			if (in_array($value, $validOptions, true)) {
				$route->$value(true);
			}
		}
	}

	/**
	 * 从action数组中找到可调用对象
	 */
	public function findCallable(array $action) {
		foreach ($action as $key => $value) {
			if (is_callable($value) && is_numeric($key)) {
				return $value;
			}
		}
	}

	/**
	 * 更新路由分组属性栈 分组嵌套使用时，子分组将继承父分组的属性
	 */
	public function updateGroupStack(array $attributes) {

		if(!empty($this->groupStack)) {
			//获取父分组的属性
			$parentAttrs = end($this->groupStack);

			//继承父分组的属性 修改 命名空间
			$this->updateGroupAttribute('namespace', $attributes, $parentAttrs, '\\');

			//继承父分组的属性 修改 路由地址前缀
			$this->updateGroupAttribute('prefix', $attributes, $parentAttrs, '/');

			//继承父分组的属性 修改 路由名称
			$this->updateGroupAttribute('name', $attributes, $parentAttrs, '');
			
			//其他属性可以直接合并
			$this->groupStack[] = array_merge($parentAttrs, $attributes);
		} else {
			$this->groupStack[] = $attributes;
		}
	}

	/**
	 * 使用父分组的属性 修改分组属性
	 * @param string $attrName  	属性名
	 * @param array  $attributes 	分组属性
	 * @param array  $parentAttrs 	父分组属性
	 * @param array  $delimiter 	连接符
	 */
	public function updateGroupAttribute($attrName, &$attributes, $parentAttrs, $delimiter) {
		if (isset($attributes[$attrName], $parentAttrs[$attrName])) {
			$attributes[$attrName] = trim($parentAttrs[$attrName], $delimiter).$delimiter.trim($attributes[$attrName], $delimiter);
		}
	}

	/**
	 * 路由模型绑定
	 * @param  string $key 				路由参数
	 * @param  string $modelClassName	模型类名
	 * @return array|false
	 */
	public function model($key, $modelClassName) {
		$this->binders[$key] = function ($value) use ($modelClassName) {
			
			$instance = $this->application->make($modelClassName);
			
			return $instance->where($instance->getPrimaryKey(), $value)->find();
		};
	}

	/**
	 * 路由模型绑定(使用自己的解析逻辑) 
	 * @param string  $key   	路由参数
	 * @param closure $callback 自定义解析逻辑
	 */	
	public function bind($key, Closure $callback) {
		$this->binders[$key] = $callback;
	}

	/**
	 * 获取路由模型绑定信息
	 */
	public function getBinders() {
		return $this->binders;
	}

	/**
	 * 对某个路由参数，设置全局性的正则表达式约束
	 */
	public function pattern($key, $pattern) {
		$this->patterns[$key] = $pattern;
	}

	/**
	 * 对多个路由参数，设置全局性的正则表达式约束
	 */
	public function patterns(array $patterns) {
		foreach($patterns as $key => $pattern) {
			$this->pattern($key, $pattern);
		}
	}

	/**
	 * 获取全局性的正则表达式约束
	 */
	public function getPatterns() {
		return $this->patterns;
	}
}