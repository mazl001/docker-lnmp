<?php
namespace Onion\Database;

use Onion\Database\DatabaseManager;

class Model {

	/**
	 * 数据表名称 (不含前缀, 系统会自动添加前缀)
	 */
	protected $table;

	/**
	 * 数据库连接名称 (配置文件 config/databasa.php 里connections数组的键名)
	 */
	protected $connection = null;

	/**
	 * 数据表主键
	 */	
	protected $primaryKey = 'id';

	/**
	 * 数据库管理类
	 */
	protected static $databaseManager;
	
	/**
	 * 构造函数
	 */
	public function __construct() {
		//默认使用 Model 类文件名部分作为表名
		if (empty($this->table)) {
            $this->table = basename(str_replace("\\", DIRECTORY_SEPARATOR, 
            	strtolower(static::class)));
		}
	}

	/**
	 * 获取数据表主键
	 */
	public function getPrimaryKey() {
		return $this->primaryKey;
	}

	/**
	 * 获取连接名称 (配置文件 config/databasa.php 里connections数组的键名)
	 */
	public function getConnection() {
		return $this->connection;
	}

	/**
	 * 设置连接名称, 可用于分库 (配置文件 config/databasa.php 里connections数组的键名)
	 */
	public function setConnection(...$args) {
		$this->connection = $args[0];
		return $this;
	}

	/**
	 * 获取表名
	 */
	public function getTable() {

		return $this->table;
	}

	/**
	 * 设置表名, 可用于分表
	 */
	public function setTable(...$args) {
		$this->table = $args[0];
		return $this;
	}

	/**
	 * 设置数据库管理类
	 */
	public static function setDatabaseManager(DatabaseManager $databaseManager) {
		static::$databaseManager = $databaseManager;
	}

	
	public function __call($method, $args) {
		
		$query = static::$databaseManager->getConnection($this->getConnection())
										 ->table($this->getTable());

		return call_user_func_array([$query, $method], $args);
	}
}