<?php
namespace Onion\Container;

use Closure;
use Exception;
use ReflectionClass;
use ReflectionFunction;
use ReflectionMethod;

class Container {

    /**
     * 容器自身实例
     */
    protected static $instance;
    
    /**
     * 绑定: 抽象名称 => 具体实现方式 (类名、闭包)
     */
    protected $bindings = [];

    /**
     * 绑定: 抽象名称 => 具体实现方式 (对象)
     */
    protected $instances = [];

    /**
     * 别名
     * 数组结构：完整类名 => 简略抽象名称
     */
    protected $aliases = [];


    /**
     * 根据抽象名称, 从容器中获取具体实例
     * @param string $abstract  抽象名称
     */
    public function __get($abstract) {
    	$abstract = $this->getAlias($abstract);

    	if (isset($this->bindings[$abstract]) || isset($this->instances[$abstract])) {
    		return $this->make($abstract);
    	}

    	throw new Exception('property not exists: '.$abstract);
    }


 	/**
 	 * 绑定: 抽象名称 => 具体实现方式 (类名、闭包)
     * @param string         $abstract   抽象名称
     * @param string|closure $concrete   具体实现方式
     * @param bool           $singleton  是否单例模式
     */
	public function bind($abstract, $concrete, $singleton = false) {
		$this->bindings[$abstract] = compact('concrete', 'singleton');	
	}


	/**
     * 绑定: 抽象名称 => 具体实现方式 (类名、闭包)，且使用单例模式
	 */
	public function singleton($abstract, $concrete) {
		$this->bind($abstract, $concrete, true);
	}


    /**
     * 绑定: 抽象名称 => 具体实现方式 (对象)
     * @param string $abstract  抽象名称
     * @param object $instance  对象
     */
    public function instance($abstract, $instance) {
        $this->instances[$abstract] = $instance;
    }


	/**
     * 根据抽象名称, 从容器中解析具体实例
     * @param string $abstract    抽象名称
     * @param array  $parameters  解析参数
	 */
	public function make($abstract, $parameters = []) {

    	$abstract = $this->getAlias($abstract);

		//已实例化对象，直接返回
		if (isset($this->instances[$abstract])) {
			return $this->instances[$abstract];
		}

        //如果已在容器中绑定具体实现方式，提取出绑定的concrete, singleton属性
		if (isset($this->bindings[$abstract])) {
			extract($this->bindings[$abstract]);
        //如果未在容器中绑定具体实现方式，尝试将$abstract作为类名进行实例化
		} else {
			list($concrete, $singleton) = [$abstract, false];
		}

		if ($concrete instanceof Closure) {
			//$concrete是闭包时
			$object = $this->invokeClosure($concrete, $parameters);
		} else {
			//$concrete是类名(字符串)时
			$object = $this->getNewInstanceByClassName($concrete, $parameters);
		}

		if ($singleton) $this->instance($abstract, $object);
		return $object;
	}


	/**
	 * 根据类名，创建类实例，自动解决参数依赖
     * @param string $concrete    类名
     * @param array  $parameters  构造函数参数
	 */
	public function getNewInstanceByClassName($concrete, $parameters = []) {
		$reflectionClass = new ReflectionClass($concrete);

		$construtor = $reflectionClass->getConstructor();
		$reflectionParameters = $construtor ? $construtor->getParameters() : [];
		$dependencies = $this->getDependecies($reflectionParameters, $parameters);

		return $reflectionClass->newInstanceArgs($dependencies);
	}

	/**
	 * 调用匿名函数，自动解决参数依赖
     * @param closure $concrete    匿名函数
     * @param array   $parameters  匿名函数参数
	 */
	public function invokeClosure(Closure $concrete, $parameters = []) {
		$reflectionFunction = new ReflectionFunction($concrete);

		$reflectionParameters = $reflectionFunction->getParameters();
		$dependencies = $this->getDependecies($reflectionParameters, $parameters);
		
        return call_user_func_array($concrete, $dependencies);
	}
	

	/**
	 * 调用对象的方法，自动解决参数依赖
     * @param object  $instance    对象
     * @param string  $method      方法名称
     * @param array   $parameters  方法参数
	 */
	public function invokeMethod($instance, $method, $parameters = []) {
		$reflectionMethod = new ReflectionMethod($instance, $method);

		$reflectionParameters = $reflectionMethod->getParameters();
		$dependencies = $this->getDependecies($reflectionParameters, $parameters);

        return call_user_func_array([$instance, $method], $dependencies);
	}


	/**
	 * 解决参数依赖关系
	 * @param ReflectionParameters $reflectionParameters 
	 * @param array                $parameters: 自定义参数
	 */
	public function getDependecies($reflectionParameters, $parameters = []) {
		$dependencies = [];

		foreach($reflectionParameters as $reflectionParameter) {
			$parameterName 		= $reflectionParameter->getName();
			$parameterPosition 	= $reflectionParameter->getPosition();

			//自定义参数为关联数组时
			if (array_key_exists($parameterName, $parameters)) {
				$dependencies[] = $parameters[$parameterName];
			//自定义参数为数值数组时
			} else if (array_key_exists($parameterPosition, $parameters)) {
				$dependencies[] = $parameters[$parameterPosition];
			//参数有默认值时
			} else if ($reflectionParameter->isOptional()) {
				$dependencies[] = $reflectionParameter->getDefaultValue();
			//参数为非内置类(如自定义类)时
			} else if ($reflectionParameter->getClass()) {
				$dependencies[] = $this->make($reflectionParameter->getClass()->getname());
			//参数为内置类(如string、int)时，无法解析
			} else {
				throw new Exception('Cannot resolve the parameter:' . $parameterName);
			}
		}

		return $dependencies;
	}


	/*
	 * 根据完整类名，获取简略抽象名称，如果有的话
	 * 例如: 输入 Onion\Container\Application, 返回 app
	 */
	public function getAlias($abstract) {
		return $this->aliases[$abstract] ?? $abstract;
	}

    /**
     * 获取容器自身实例
     */
    public static function getInstance() {
        return static::$instance;
    }
}

