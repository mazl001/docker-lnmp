<?php
namespace Onion\Session\handler;

use Onion\Database\drivers\PDOConnection;
use SessionHandlerInterface;


/**
 * 自定义会话存储: 数据库
 * @see https://www.php.net/manual/zh/class.sessionhandlerinterface.php
 */
class DatabaseSessionHandler implements SessionHandlerInterface {
	
	/**
	 * 数据库连接实例
	 */
	protected $connection;

	/**
	 * session 表名
	 */
	protected $table;

	/**
	 * 过期时间 单位: 分钟
	 */
	protected $lifetime;

    /**
     * 构造函数
     */
	public function __construct(PDOConnection $connection, string $table, int $lifetime) {
		$this->connection = $connection;

		$this->table      = $table;

		$this->lifetime   = $lifetime;
	}

    /**
     * 读取Session数据
     * @return string|空字符串
     */
    public function read($session_id) {

        $mapExpires = _or([
            ['session_expires', 0],
            ['session_expires', '>=', time()]
        ]);

        $map = _and(['session_id' => $session_id, $mapExpires]);

    	$data = $this->getQuery()->field('session_data')->where($map)->find();

    	return $data ? $data : '';
    }

    /**
     * 写入Session数据
     * @return bool
     */
    public function write($session_id, $session_data) {
        
        if (!empty($this->lifetime)) {
            $this->lifetime = time() + $this->lifetime * 30;
        }

    	$data = [
    		'session_id'   		=> $session_id,
    		'session_data' 		=> $session_data,
    		'session_expires'	=> $this->lifetime,
    	];

    	$lastInsertId = $this->getQuery()->duplicate($data)->insert($data);

    	return !empty($lastInsertId);
    }

    /**
     * 销毁Session数据 当调用 session_destroy() 函数, 会调用此方法
     * @return bool
     */
    public function destroy($session_id) {
    	$map = [
    		'session_id' => $session_id
    	];

    	$rowCount = $this->getQuery()->where($map)->delete();
    	return !empty($rowCount);
    }

    /**
     * 清理过期的Session旧数据
     * 调用周期由 php.ini 中的 session.gc_probability 和 session.gc_divisor 参数控制
     * @return bool
     */
    public function gc($maxlifetime) {
        $map = _and([
            ['session_expires', '!=', 0],
            ['session_expires', '<=', time()]
        ]);

    	$this->getQuery()->where($map)->delete();
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


    protected function getQuery() {
    	return $this->connection->table($this->table);
    }
}