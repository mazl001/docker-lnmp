<?php
namespace Onion\Middleware;

use Closure;
use Onion\Cookie\CookieManager;
use Onion\Http\Request;


/**
 * 中间件: 添加Cookie到HTTP响应的头部
 */
class AddQueuedCookiesToResponse {

	/**
	 * Cookie管理类
	 */
	protected $cookieManager;

	/**
	 * 构造函数
	 */
	public function __construct(CookieManager $cookieManager) {
		$this->cookieManager = $cookieManager;
	}

	public function handle(Request $request, Closure $next) {
		
		$response = $next($request);		
		
		//获取队列中的Cookie数据，添加到响应头
		$queue = $this->cookieManager->getQueuedCookies();

		foreach ($queue as $cookie) {
			$response->headers->setCookie($cookie);
		}

		return $response;
	}
}