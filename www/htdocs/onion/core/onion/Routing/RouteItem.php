<?php
namespace Onion\Routing;

use Closure;
use Onion\Container\Application;
use Onion\Http\Request;
use Onion\Http\Response;
use Onion\Routing\Validators\MethodValidator;
use Onion\Routing\Validators\SchemeValidator;
use Onion\Routing\Validators\HostValidator;
use Onion\Routing\Validators\UriValidator;
use Symfony\Component\HttpFoundation\Cookie;

class RouteItem {

	/**
	 * 路由地址
	 */
	protected $uri;
	
	/**
	 * 请求方法
	 */
	protected $methods = [];
	
	/**
	 * 响应动作
	 */
	protected $action = [];

	/**
	 * 路由中间件
	 */
	protected $middleware = [];

	/**
	 * 当前路由的正则表达式约束
	 */
	protected $patterns = [];

	/**
	 * 匹配到的参数
	 */
	protected $parameters = [];

	/**
	 * 路由规则验证器
	 */
	public static $validators;

	/**
	 * 构造函数
	 */
	public function __construct($methods, $uri, $action) {
		$this->methods = (array) $methods;
		$this->uri     = $uri === '/' ? '/' : trim($uri, '/');
		$this->action  = $action;
	}

	/**
	 * 获取路由规则验证器
	 */
	public function getValidators() {
		if (isset(self::$validators)) return self::$validators;

		return self::$validators = [
			new MethodValidator, new SchemeValidator,
			new UriValidator,
		];
	}

	/**
	 * 使用 路由验证器 验证 请求和路由 是否匹配
	 */
	public function matches(Request $request) {
		foreach($this->getValidators() as $validator) {
			if (!$validator->matches($this, $request)) return false;
		}

		return true;
	}

	/**
	 * 设置路由名称 更新路由名称记录表
	 */
	public function name($name) {
		if (isset($this->action['name'])) {
			Route::updateNameList($this->action['name'], null);
		}

		$this->action['name'] = ($this->action['name'] ?? null).$name;

		Route::updateNameList($this->action['name'], $this);
		return $this;
	}

	/**
	 * 设置路由域名
	 */
	public function domain($domain) {
		$this->action['domain'] = $domain;
		return $this;
	}

	/**
	 * 设置路由地址前缀
	 */
	public function prefix($prefix) {
		$this->uri = trim($prefix, '/').'/'.trim($this->uri, '/');
		return $this;	
	}

	/**
	 * 设置路由命名空间
	 */
	public function namespace($namespace) {
		if (is_string($this->action['uses'])) {
			$this->action['uses'] = trim($namespace, '\\').'\\'.trim($this->action['uses'], '\\');
		}
		return $this;
	}

	/**
	 * 设置路由中间件
	 */
	public function middleware($middleware) {
		$this->action['middleware'] = array_merge($this->action['middleware'] ?? [], (array)$middleware);
		return $this;
	}

	/**
	 * 设置路由协议限定
	 */
	public function http($http = true) {
		$this->action['http'] = $http;
		return $this;
	}

	/**
	 * 设置路由协议限定
	 */
	public function https($https = true) {
		$this->action['https'] = $https;
		return $this;
	}

	/**
	 * 约束路由参数的格式 支持以下两种类型参数：
	 * where('id', '[0-9]+');
	 * where(['id' => '[0-9]+']);
	 */
	public function where($name, $expression = null) {
		$regularExpression = is_array($name) ? $name : [$name => $expression];

		foreach($regularExpression as $name => $expression) {
			$this->patterns[$name] = $expression;
		}

		return $this;
	}

	/**
	 * 获取路由属性数组
	 */
	public function getAction() {
		return $this->action;
	}

	/**
	 * 获取路由匹配的HTTP方法
	 */
	public function getMethods() {
		return $this->methods;
	}

	/**
	 * 获取路由地址
	 */
	public function getUri() {
		return $this->uri;
	}

	/**
	 * 获取当前路由的正则表达式约束
	 */
	public function getPatterns() {
		return $this->patterns;
	}

	/**
	 * 获取路由限定的域名
	 */
	public function getDomain() {
		return $this->action['domain'] ?? null;
	}

	/**
	 * 获取路由中间件
	 */
	public function getMiddleware() {
		return $this->action['middleware'] ?? [];
	}

	/**
	 * 设置路由参数
	 */
	public function setParameters(array $parameters) {
		$this->parameters = $parameters;
	}

	/**
	 * 执行路由action并返回结果
	 */
	public function run(Request $request, Application $application) {
		$uses = $this->action['uses'];

		if ($uses instanceof Closure) {
			$response = $application->invokeClosure($uses, $this->parameters);
		} else {
			list($class, $method) = explode('@', $uses);
			$instance = $application->getNewInstanceByClassName($class);
			$response = $application->invokeMethod($instance, $method, $this->parameters);;
		}

		//转化为Response对象
		if (!($response instanceof Response)) {
			$response = new Response($response);
		} 

		//调用SymfonyResponse->prepare()方法 主要用于处理HTTP标准的相关兼容性问题
		return $response->prepare($request);
	}
}