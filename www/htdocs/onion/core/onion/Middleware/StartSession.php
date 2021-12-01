<?php
namespace Onion\Middleware;

use Closure;
use Onion\Container\Application;
use Onion\Http\Request;
use Onion\Session\SessionManager;


/**
 * 中间件: 初始化Session
 */
class StartSession {

	/**
	 * Session 管理类
	 */
	protected $sessionManager;

	public function __construct(SessionManager $sessionManager) {
		$this->sessionManager = $sessionManager;
	}

	public function handle(Request $request, Closure $next) {
		/**
		* 中间件前置操作, 在路由Action执行前调用
		*/
		if ($this->sessionManager->sessionConfigured()) {
			//获取session驱动实例，驱动自定义了会话存储方式 session_set_save_handler
			$session = $this->sessionManager->getDriver();
			//设置session id, 生成CSRF token, 启动session
			$this->startSession($request, $session);
			//设置request session
			$request->setSession($session);
		}


		$response = $next($request);

		/**
		 * 中间件后置操作, 在路由Action执行后调用
		 */
		if ($this->sessionManager->sessionConfigured()) {
			//存储当前请求地址
			$this->storeCurrentUrl($request, $session);
			//Session id 通过 Cookie 的方式发送到浏览器
			$lifetime = $this->sessionManager->getSessionConfig('lifetime');
			Cookie::set($session->getName(), $session->getId(), $lifetime);
		}

		return $response;
	}


    /**
     * 设置SESSION ID 启动SESSION
     */
    public function startSession($request, $session) {
		//从cookie中获取SESSION ID
		$id = $request->cookies->get($session->getName());

		//如果没有有效的SESSION ID, 生成一个新的SESSION ID
		if (!_isValidToken($id)) {
			$id = _generateToken();
		}

		$session->setId($id);
		$session->start();

		//生成Token 用于CSRF验证
		if (!$session->has('_token')) {
			$session->set('_token', _generateToken());
		}
    }

    /**
     * 存储当前请求地址 用于页面跳转
     */
    protected function storeCurrentUrl($request, $session) {
    	if ($request->getMethod() == 'GET') {
    		$session->set('_previous.url', $request->getUri());
    	}
    }
}