<?php
namespace Onion\Http;

use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class Response extends SymfonyResponse {

	/**
	 * 设置HTTP头信息
	 */
	public function header($key, $value) {
		$this->headers->set($key, $value);
		return $this;
	}

	/**
	 * 添加Cookie到响应
	 */
	public function withCookie(...$parameters) {
		if (reset($parameters) instanceof Cookie) {
			$cookie = reset($parameters);
		} else {
			$cookie = call_user_func_array('Cookie::make', $parameters);
		}

		$this->headers->setCookie($cookie);
		return $this;
	}

	/**
	 * 如果响应内容是数组，将内容转化为json格式，并设置HTTP头信息
	 */
	public function setContent($content) {

		if (is_string($content) and !is_null(json_decode($content, true))) {
			$content = json_decode($content, true);
		}

		if (is_array($content)) {
			$this->header('Content-Type', 'application/json');
			$content = json_encode($content);
		}

		return parent::setContent($content);
	}
}