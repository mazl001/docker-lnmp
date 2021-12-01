<?php
namespace Onion\Session\handler;

use SessionHandlerInterface;

/**
 * 自定义会话存储: Redis
 * @see https://www.php.net/manual/zh/class.sessionhandlerinterface.php
 */
class RedisSessionHandler implements SessionHandlerInterface {
	
	/**
	 * Redis连接实例
	 */
	protected $connection;

	/**
	 * 过期时间 单位: 分钟
	 */
	protected $lifetime;

	/**
	 * 键前缀
	 */
	protected $prefix = 'SESSION_';

	/**
	 * 构造函数
	 */
	public function __construct($connection, $lifetime) {
		$this->connection = $connection;

		$this->lifetime   = $lifetime;
	}

    /**
     * 读取Session数据
     * @return string|空字符串
     */
    public function read($session_id) {
    	if ($data = $this->connection->get($this->prefix.$session_id)) {
    		return $data;
    	}

    	return '';
    }

    /**
     * 写入Session数据
     * @return bool
     */
    public function write($session_id, $session_data) {

        if ($this->connection->set($this->prefix.$session_id, $session_data)) {

            //设置过期时间
            if ($this->lifetime) {
                $this->connection->expire($this->prefix.$session_id, $this->lifetime * 60);
            }

            return true;
        }

        return false;
    }


    /**
     * 销毁Session数据 当调用 session_destroy() 函数, 会调用此方法
     * @return bool
     */
    public function destroy($session_id) {
    	
    	if ($this->connection->del($this->prefix.$session_id)) {
    		return true;
    	}

    	return false;
    }

    /**
     * 清理过期的Session旧数据
     * 调用周期由 php.ini 中的 session.gc_probability 和 session.gc_divisor 参数控制
     * @return bool
     */
    public function gc($maxlifetime) {

        //Redis不需要手动清理过期的数据
    	return true;
    }

    /**
     * 在会话打开的时候被调用
     * @return bool
     */
    public function open($save_path, $session_name) {
    	return true;
    }

    /**
     * 在 write 回调函数调用之后调用
     * @return bool
     */
    public function close() {
    	return true;
    }
}