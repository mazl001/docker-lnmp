<?php
namespace Onion\Routing\Validators;

use Onion\Facades\Route;
use Onion\Routing\RouteItem;
use Onion\Http\Request;

class UriValidator implements ValidatorInterface {

	/**
	 * 基础的正则约束
	 */
	protected $patterns = array(
		'[\w]+'   	  => '[^\/]+',   //匹配必选的路由参数
		'[\w]+\??'    => '[^\/]*',   //匹配可选的路由参数
 	);


	public function matches(RouteItem $route, Request $request) {

		//获取正则表达式约束，包括：当前路由(优先级最高)、 全局性、 基础的正则表达式约束
		$patterns = array_merge($route->getPatterns(), Route::getPatterns(), $this->patterns);

		foreach ($patterns as $key => $value) {
			$searches[] = '#\{'.$key.'\}#';
			$replaces[] = '('.$value.')';
		}

		//是否需要验证域名
		if ($route->getDomain()) {
			$routeUrl    = $route->getDomain(). '/' .ltrim($route->getUri(), '/');
			$requestUrl  = $request->getHost(). '/' .ltrim($request->getPath(), '/'); 
		} else {
			$routeUrl    = $route->getUri();
			$requestUrl  = $request->getPath();
		}

		//提取URL参数值
		$compiledUri = '#^'.preg_replace($searches, $replaces, $routeUrl).'$#';
		$matched = preg_match($compiledUri, $requestUrl, $parameters);

		//提取路由参数名称
		$compiledUri = '#^'.preg_replace('#{[\w]+\??}#', '{([\w]+)\??}', $routeUrl).'$#';
		preg_match($compiledUri, $routeUrl, $matches);

		//如果URL与路由地址匹配，设置路由参数
		if ($matched and count($parameters) > 1) {

			$keys   = array_values(array_slice($matches, 1));
			$values = array_values(array_slice($parameters, 1));

			//用参数名称作为其键名，URL参数值作为其值 (过滤空值: 未赋值的路由可选参数)
			$parameters = array_filter(array_combine($keys, $values));
			
			//对绑定模型的路由参数, 进行解析
			$binders = Route::getBinders();
			array_walk($parameters, function(&$value, $key) use ($binders) {

				if (array_key_exists($key, $binders)) {
					$value = call_user_func($binders[$key], $value);
				}
			});
			
			$route->setParameters($parameters);
		}

		return $matched;
	}
}