<?php
namespace Onion\Http;

use Onion\Container\Application;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

/**
 * 请求管理类
 */
class Request extends SymfonyRequest {

    /**
     * json请求数据(已解码)
     */
    protected $json;


	/**
	 * 创建request对象
	 */
	public static function capture() {
		//HTML 表单中 _method 字段送出的值将被作为 HTTP 的请求方法使用
		static::enableHttpMethodParameterOverride();
		
		return Request::createFromGlobals();
	}

	/**
	 * 获取URL路径部分
	 */
	public function getPath() {
		
		$path = urldecode($this->getPathInfo());

		//删除URL路径最前面的斜杆
		if ($path !== '/') {
			$path = ltrim($path, '/');
		}

		return $path;
	}

	/**
	 * 判断是否Json请求
	 */
	public function isJson() {
		return $this->headers->get('Content-Type') == 'application/json';
	}

	/**
	 * 获取请求参数
	 */
	public function getInputSource() {

		if ($this->isJson()) {
			return $this->json = new ParameterBag(json_decode($this->getContent(), true));
		}

		return $this->getRealMethod() == 'GET' ? $this->query : $this->request;
	}

	/**
	 * 获取某个请求参数
	 */
	public function input($key = null) {

		$input = $this->getInputSource()->all() + $this->query->all();

		return $key ? $input[$key] : $input;
	}
}