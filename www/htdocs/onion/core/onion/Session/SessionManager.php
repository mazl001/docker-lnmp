<?php
namespace Onion\Session;

use Onion\Container\Application;
use Onion\Session\handler\DatabaseSessionHandler;
use Onion\Session\handler\RedisSessionHandler;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;


class SessionManager {
	
	/**
	 * 应用实例
	 */
	protected $application;

	/**
	 * Session驱动实例，驱动自定义了会话存储方式 session_set_save_handler
	 */
	protected $drivers = [];

	/**
	 * 构造函数
	 */
	public function __construct(Application $application) {
		$this->application = $application;
	}

	/**
	 * session驱动 是否 已配置
	 */
	public function sessionConfigured() {
		return !empty($this->getSessionConfig('driver'));
	}


	/**
	 * 获取session配置
	 */
	public function getSessionConfig($name = null) {
		$name = $name ? "session.$name" : "session";
		return $this->application->config[$name];
	}

	/**
	 * 获取驱动实例
	 */
	public function getDriver($driver = null) {
		$driver = $driver ?: $this->getSessionConfig('driver');

		if (!isset($this->drivers[$driver])) {
			$this->drivers[$driver] = $this->createDriver($driver);
		}

		return $this->drivers[$driver];
	}

	/**
	 * 创建驱动实例
	 */
	public function createDriver($driver) {
		//自定义会话存储方式, 例如, 可以将Session数据存储到数据库
		$method = 'create'.ucfirst(strtolower($driver)).'Handler';
		$handler = $this->$method();

		$sessionStorage = new NativeSessionStorage([], $handler);
		$session = new Session($sessionStorage);

		return $session;
	}

	/**
	 * 自定义会话存储方式: 文件
	 */
	public function createFileHandler() {
		return new NativeFileSessionHandler($this->getSessionConfig('files'));
	}

	/**
	 * 自定义会话存储方式: 数据库
	 */
	public function createDatabaseHandler() {
		
		$connectionName = $this->application->config['database.default'];

		$table = $this->getSessionConfig('table');

		$lifetime = $this->getSessionConfig('lifetime');

		$connection = $this->application->db->getConnection($connectionName);
		
		return new DatabaseSessionHandler($connection, $table, $lifetime);
	}


	/**
	 * 自定义会话存储方式: Redis
	 */
	public function createRedisHandler() {

		$connectionName = $this->application->config['redis.default'];
		
		$connection = $this->application->redis->getConnection($connectionName);

		$lifetime = $this->getSessionConfig('lifetime');

		return new RedisSessionHandler($connection, $lifetime);
	}

	/**
	 * 调用驱动实例的方法
	 */
	public function __call($method, $args) {

		if ($method == 'removeAll') {
			return call_user_func_array([$this->getDriver(), 'clear'], $args);
		} else {
			return call_user_func_array([$this->getDriver(), $method], $args);
		}
	}
}