<?php
namespace Onion\Database;

use Predis\Client;


/**
 * redis管理类
 */
class Redis {

	/**
	 * 配置信息
	 */
	protected $config = [];

	/**
	 * Redis连接
	 */
	protected $clients = [];

	/**
	 * 构造函数
	 */
	public function __construct(array $config) {
		$this->config = $config;
	}

	/**
	 * 获取redis连接
	 */
	public function getConnection($name = null) {
		//获取数据连接标识
		$name = $name ?? $this->config['default'];
		
		if (!isset($this->clients[$name])) {
			//获取配置信息
			$connectionsConfig = $this->config[$name];
		
			$this->clients[$name] = new Client($connectionsConfig['servers'], 
				$connectionsConfig['options']);
		}
		
		return $this->clients[$name];
	}

	/**
	 * 调用redis命令
	 */
	public function __call($command, $args) {
		return call_user_func_array([$this->getConnection(), $command], $args);
	}
}