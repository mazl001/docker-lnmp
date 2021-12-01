<?php
namespace Onion\Middleware;

use Closure;
use Onion\Container\Application;
use Onion\Http\Request;
use Onion\Http\Response;
use Onion\Support\Encrypter;
use Symfony\Component\HttpFoundation\Cookie;

/**
 * 中间件: 加密、解密Cookie
 */
class EncryptCookies {

	/**
	 * 不加密的cookie键名
	 */
    protected $except = [];

    /**
     * 加密器
     */
    protected $encrypter;

    /**
     * 构造函数
     */
    public function __construct(Encrypter $encrypter) {
    	$this->encrypter = $encrypter;
    }


	public function handle(Request $request, Closure $next) {
		return $this->encrypt($next($this->decrypt($request)));
	}

	/**
	 * 对请求中的cookie进行解密
	 */
	public function decrypt(Request $request) {
		foreach ($request->cookies as $name => $value) {
			if (!in_array($name, $this->except)) {
				$request->cookies->set($name, $this->encrypter->decrypt($value));
			}
		}

		return $request;
	}

	/**
	 * 对响应中的cookie进行加密
	 */
	public function encrypt(Response $response) {
        foreach ($response->headers->getCookies() as $cookie) {

     		if (!in_array($cookie->getName(), $this->except)) {

     			$encryptedCookie = new Cookie(
     				$cookie->getName(), 
     				$this->encrypter->encrypt($cookie->getValue()),
     				$cookie->getExpiresTime(),
     				$cookie->getPath(), 
     				$cookie->getDomain(),
     				$cookie->isSecure(), 
     				$cookie->isHttpOnly()
     			);

     			$response->headers->setCookie($encryptedCookie);
     		}
        }

		return $response;
	}
}