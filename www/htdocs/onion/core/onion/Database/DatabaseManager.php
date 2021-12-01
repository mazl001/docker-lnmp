<?php
namespace Onion\Database;

use Exception;
use Onion\Container\Application;
use Onion\Events\Observable;

/**
 * 数据库管理类
 */
class DatabaseManager {

    /**
     * 数据库配置
     */
    protected $config = [];

    /**
     * 数据库连接
     */
    protected $connections = [];

    /**
     * 构造函数
     * @param array       $config       数据库配置信息
     * @param Observable  $dispatcher   事件调度器
     */
    public function __construct(array $config, Observable $dispatcher) {
        $this->config       = $config;
        $this->dispatcher   = $dispatcher;
    }

    /**
     * 获取相应的数据库驱动实例 (此处没有真正去连接数据库)
     * @param string $name 连接标识符, 配置文件 config/databasa.php 里connections数组的键名
     */
    public function getConnection(String $name = null) {
        //获取默认数据连接标识
        $name = $name ?? $this->config['default'];

        if (!isset($this->connections[$name])) {
            //获取配置信息
            $connectionsConfig = $this->config['connections'][$name];

            //根据数据库类型: mysql、mongo等, 创建数据库驱动实例
            $driver = __NAMESPACE__.'\\drivers\\'.ucfirst(strtolower($connectionsConfig['driver']));
            if (!class_exists($driver)) {
                throw new Exception("Unsupported driver [{$connectionsConfig['driver']}]");
            }

            $connection = new $driver($connectionsConfig, $this->dispatcher);
            $this->connections[$name] = $connection;
        }

        return $this->connections[$name];
    }
    
    public function __call($method, $params) {
        return call_user_func_array([$this->getConnection(), $method], $params);
    }
}