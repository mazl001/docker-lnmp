<?php
namespace Onion\Support;

use ArrayAccess;

class ConfigurationRepository implements ArrayAccess{
    
    /**
     * 配置信息
     */
	protected $config = [];


	/**
	 * 获取配置信息
	 * @param string $key 配置名称, 如：app, app.timezone, database.connections.mysql.driver
	 */
	public function get($key, $default = null) {

		$result = [];
		$stack  = explode('.', $key);

		while (!empty($stack)) {
			$current = array_shift($stack);

			if (empty($result)) {
				$result = $this->config[$current];
			} else {
				$result = $result[$current] ?? $default;
			}
		}

		return $result;
	}

	/**
	 * 设置配置信息
	 * @param string $key    配置名称
	 * @param array  $value  配置信息数组
	 */
	public function set($key, array $value) {
		$this->config[$key] = $value;
	}
	
	
	/**
	 * @param mixed $offset
	 */
	public function offsetExists ($offset) {
	   return $this->get($offset) !== null;
	}
	
	/**
	 * @param mixed $offset
	 */
	public function offsetGet ($offset) {
	    return $this->get($offset);
	}
	
	/**
	 * @param mixed $offset
	 * @param mixed $value
	 */
	public function offsetSet ($offset, $value) {
	    $this->set($offset, $value);
	}
	
	/**
	 * @param mixed $offset
	 */
	public function offsetUnset ($offset) {
	    
	}
}