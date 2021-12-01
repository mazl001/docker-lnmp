<?php
namespace Onion\Cookie;

use Onion\Container\Application;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * Cookie 管理类
 */
class CookieManager {
	
	/**
	 * Cookie 队列
	 */
	protected $queuedCookies = [];

	/**
	 * Cookie 默认的过期时间
	 */
	protected $minutes;

	/**
	 * Cookie 默认的有效的服务器路径
	 */
	protected $path;

	/**
	 * Cookie 默认的有效域名/子域名
	 */
	protected $domain;

	/**
	 * 是否仅仅通过 HTTPS 连接传给客户端
	 */
	protected $secure;

	/**
	 * 是否仅可通过 HTTP 协议访问
	 */
	protected $httpOnly;

	public function __construct($application, $minutes, $path, $domain, $secure, $httpOnly) {
		$this->application 	= $application;
		$this->minutes  	= $minutes;
		$this->path     	= $path;
		$this->domain   	= $domain;
		$this->secure   	= $secure;
		$this->httpOnly 	= $httpOnly;
	}
	
	/**
	 * 从Request中获取某个Cookie
	 */
	public function get($key, $default = null) {
	    return $this->application->request->cookies->get($key, $default);
	}

	/**
	 * 从Request中获取所有Cookie
	 */
	public function all() {
		return $this->application->request->cookies->all();
	}

	/**
	 * 添加Cookie到队列
	 */
	public function set(...$parameters) {
		if (reset($parameters) instanceof Cookie) {
			$cookie = reset($parameters);	
		} else {
			$cookie = call_user_func_array([$this, 'make'], $parameters);
		}

		$this->queuedCookies[] = $cookie;
	}


	/**
	 * 删除单个Cookie
	 */
	public function remove($name) {
		$cookie = call_user_func_array([$this, 'make'], [$name, null]);

		$this->queuedCookies[] = $cookie;
	}


	/**
	 * 删除所有Cookie
	 */
	public function removeAll() {
		$cookies = $this->all();

		foreach ($cookies as $name => $value) {
			$this->remove($name);
		}
	}

	/**
	 * 创建Cookie对象
	 */
	public function make(string $name, string $value = null, $minutes = null, $path = null, $domain = null,  $secure = null, $httpOnly = null) {

		$minutes = $minutes ?? $this->minutes;
		if (!empty($minutes)) $minutes = time() + $minutes * 60;

		list($path, $domain, $secure, $httpOnly) = [
			$path 		?? $this->path,
			$domain 	?? $this->domain,
			$secure		?? $this->secure,
			$httpOnly 	?? $this->httpOnly
		];

		$cookie = new Cookie(
			$name, $value, $minutes, $path, $domain, $secure, $httpOnly
		);

		return $cookie;
	}

	/**
	 * 获取队列内的Cookie数据
	 */
	public function getQueuedCookies() {
		return $this->queuedCookies;
	}
}