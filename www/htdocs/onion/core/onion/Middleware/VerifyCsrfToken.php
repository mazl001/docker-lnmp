<?php
namespace Onion\Middleware;

use Closure;
use Exception;
use Onion\Container\Application;
use Onion\Http\Request;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * 中间件: 验证 CSRF token
 */
class VerifyCsrfToken {

	/**
	 * 不需要CSRF验证的路径 支持stripe/*格式,
	 */
	protected $except = [];


	public function handle(Request $request, Closure $next) {
		if ($this->isReading($request) || $this->shouldPassThrough($request) || $this->tokensMatch($request)) {
			return $next($request);
		}

		throw new Exception('Token Mismatch.');
	}

	/**
	 * 判断是否 读 请求
	 */
	protected function isReading($request) {
		return in_array($request->getMethod(), ['GET', 'HEAD', 'OPTIONS']);
	}

	/**
	 * 判断是否不受 CSRF 保护的 URIs
	 */
	protected function shouldPassThrough($request) {
		//getPath函数返回原始路径, 未经过urldecode解密
		$path = urldecode($request->getPath());

		foreach ($this->except as $except) {
			if ($except !== '/') {
				$except = trim($except, '/');
			}

			if (preg_match('#^'.str_replace('*', '.*', $except).'#u', $path)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * 验证token
	 */
	public function tokensMatch($request) {
		$sessionToken = $request->getSession()->get('_token');
		$token = $request->input('_token') ?: $request->headers->get('X-CSRF-TOKEN');

		if (!is_string($sessionToken) || !is_string($token)) {
			return false;
		}
		
		return $sessionToken === $token;
	}
}