<?php
namespace Onion\Events;

use Closure;
use ReflectionClass;
use Onion\Container\Application;


/**
 * 事件调度类
 */
class Dispatcher implements Observable {

	/**
	 * 应用实例
	 */
	protected $application;

	/**
	 * 事件观察者
	 */
	protected $observers;
	
	/**
	 * 配置信息
	 */
	protected $config;

	/**
	 * 构造函数
	 */
	public function __construct(Application $application) {
		
		$this->application = $application;
	}

	/**
	 * 添加事件观察者 (监听单个事件)
	 * @param string $event     事件名称
	 * @param mixed  $observer  观察者: 匿名函数|类名
	 * @param string $handle    回调方法名称
	 */
	public function attach(string $event, $observer, $method = 'handle') {

		//判断观察者是否已添加
		if (!isset($this->observers[$event]) || !in_array($observer, $this->observers[$event])) {
			if ($observer instanceof Closure) {
				$this->observers[$event][] = $observer;
			} else {
				$this->observers[$event][] = [$observer, $method];
			}
		}
	}

	/**
	 * 添加事件观察者 (监听多个事件)
	 * @param string $subscriber 	观察者: 类名
	 */
	public function subscribe(string $subscriber) {

		$class = new ReflectionClass($subscriber);
		$methods = $class->getMethods();

		foreach ($methods as $method) {
			$methodName = $method->getName();

			if (substr($methodName, 0, 2) === 'on') {
				$this->attach(lcfirst(substr($methodName, 2)), $subscriber, $methodName);
			}
		}
	}

	/**
	 * 触发事件 通知事件观察者
	 */
	public function notify(string $event, array $parameters = []) {
		$result = [];

		$observers = $this->observers[$event] ?? [];
		foreach($observers as $observer) {

			if ($observer instanceof Closure) {
				$result[] = $this->application->invokeClosure($observer, $parameters);
			} else {
				list($observer, $method) = $observer;
				$instance = $this->application->getNewInstanceByClassName($observer);
				$result = $this->application->invokeMethod($instance, $method, $parameters);
			}
		}

		return $result;
	}
}