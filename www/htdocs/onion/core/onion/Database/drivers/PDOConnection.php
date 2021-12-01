<?php
namespace Onion\Database\drivers;

use PDO;
use PDOStatement;
use Closure;
use Exception;
use Onion\Database\Query;
use Onion\Events\Observable;


abstract class PDOConnection {

	/**
	 * 数据库配置
	 */
	protected $config;

	/**
	 * SQL语句构建工具
	 */
	protected $builder;

	/**
	 * PDOStatement 对象
	 */
	protected $PDOStatement;

	/**
	 * 主库的PDO连接对象
	 */
	protected $masterConnection;

	/**
	 * 从库的PDO连接对象
	 */
	protected $slaveConnection;

	/**
	 * 记录执行的SQL语句(用于生成最后执行SQL语句)
	 */
	protected $lastSQL;

    /**
     * 记录PDO绑定的参数(用于生成最后执行SQL语句)
     */
	protected $PDOValues = [];

	/**
	 * 事件调度器
	 */
	protected $dispatcher;

	/**
	 * 控制查询返回数组的格式
	 * @see https://www.php.net/manual/zh/pdostatement.fetch.php
	 */
	protected $fetchStyle = PDO::FETCH_ASSOC;
	
	/**
	 * 数据库连接选项的键=>值数组
	 * @see https://www.php.net/manual/zh/pdo.setattribute.php
	 */
	protected $options = [
		PDO::ATTR_CASE 				=> PDO::CASE_NATURAL, //强制列名为指定的大小写: 否
		PDO::ATTR_ERRMODE 			=> PDO::ERRMODE_EXCEPTION, //错误报告: 抛出 exceptions 异常
		PDO::ATTR_ORACLE_NULLS 		=> PDO::NULL_NATURAL, //转换 NULL 和空字符串: 不转换
		PDO::ATTR_STRINGIFY_FETCHES => false, //提取的时候将数值转换为字符串: false
		PDO::ATTR_EMULATE_PREPARES  => false, //启用或禁用预处理语句的模拟: false
	];

	/**
	 * 构造函数
	 */
	public function __construct(array $config, Observable $dispatcher) {
		$this->config 	  = $config;
		$this->dispatcher = $dispatcher;
	}

	/**
	 * 指定表名 (程序自动加上配置的表前缀)
	 */
	public function table($table) {
		$prefix = $this->config['prefix'] ?? null;
		return (new Query($this, $prefix))->table($table);
	}

	/**
	 * 指定表名 (程序 【不会】 自动加上配置的表前缀)
	 */
	public function tableWithoutPrefix($table) {
		return (new Query($this))->table($table);
	}

	/**
	 * SQL语句构造器
	 */
	public function newBuilder(Query $query) {
		$builderClass = '\\Onion\\Database\\builder\\'.ucfirst($this->config['driver']);

		if (!class_exists($builderClass)) {
			throw new Exception("Unsupported builder [{$this->config['driver']}]");
		}

		return new $builderClass($query);
	}

	/**
	 * 获取PDO连接
	 * @param  bool 是否使用主库
	 * @return PDO  返回 PDO 连接对象  
	 */
	public function connect($master = true) {
		$master = $master || count($this->config['servers']['host']) === 1;

		//写操作，如果已连接过主数据库，后续都会使用同一服务器
		if ($master and isset($this->masterConnection)) {
			return $this->masterConnection;
		}

		//读操作，如果已连接过从数据库，后续都会使用同一服务器
		if (!$master and isset($this->slaveConnection)) {
			return $this->slaveConnection;
		}

		//主库使用零号服务器, 从库随机使用其他服务器
		$number = $master ? 0 : mt_rand(1, count($this->config['servers']['host']) - 1);
		foreach ($this->config['servers'] as $key => $value) {
			$databasaConfig[$key] = $value[$number] ?? $value[0];
		}

		//创建PDO实例
		$pdo = new PDO($this->getDSN($databasaConfig), $databasaConfig['username'], 
			$databasaConfig['password'], $this->options);

		if ($master) {
			return $this->masterConnection = $pdo;
		} else {
			return $this->slaveConnection  = $pdo;
		}		
	}

	/**
	 * 执行预处理语句
	 * @return bool  成功时返回 true，失败时返回 false.
	 */
	public function execute($SQL, $PDOValues = [], $master = true) {
		try {
			$pdo = $this->connect($master);
			$this->PDOStatement = $pdo->prepare($SQL);

			//绑定值到预处理SQL语句中对应的命名占位符
			foreach($PDOValues as $parameter => $value) {
				$this->PDOStatement->bindValue($parameter, $value);
			}

			//记录执行的SQL语句、参数
			$this->lastSQL   = $SQL;
			$this->PDOValues = $PDOValues;

			return $this->PDOStatement->execute();
		} catch (Exception $e) {
			throw new Exception($this->sql().' '.$e->getMessage());
		}
	}

	/**
	 * 数据库操作：增删改查
	 */
	public function __call($method, $args) {
		$method = strtolower($method);
		if (!in_array($method, ['insert', 'delete', 'update', 'select', 'find'])) {
			throw new Exception("Unsupported method [$method]");
		}

		//根据Query存储的属性, 构造SQL语句
		$query = $args[0];
		$SQL   = call_user_func([$this->newBuilder($query), 'build'.ucfirst($method).'SQL']);
		$PDOValues = $query->getPDOValues();

		//execute参数为False时, 只返回SQL语句, 不到数据库里执行
		$execute = $query->getOptions('execute');
		if ($execute === false) return [$SQL, $PDOValues];

		//判断使用主库还是从库, 执行SQL语句
		$master = $query->getOptions('master') ?? ($method === 'select' ? false : true);
		$this->execute($SQL, $PDOValues, $master);

		//返回SQL语句执行结果
		$result = call_user_func([$this, 'get'.ucfirst($method).'Result']);

		//触发事件, 通知观察者
		$event = $query->getOptions('event');
		$this->dispatcher->notify($event ?? $method, [$SQL, $PDOValues, $result]);
		return $result;
	}

	/**
	 * 返回操作结果：增
	 * @return int  默认返回插入行的ID. 批量插入时, 返回受影响行数
	 */
	public function getInsertResult() {
		$id = $this->masterConnection->lastInsertId();
		if (empty($id)) {
			$id = $this->PDOStatement->rowCount();	
		}
		
		return $id;
	}

	/**
	 * 返回操作结果：删
	 * @return int  返回受影响行数
	 */
	public function getDeleteResult() {
		$id = $this->PDOStatement->rowCount();
		return $id;
	}

	/**
	 * 返回操作结果：改
	 * @return int  返回受影响行数
	 */
	public function getUpdateResult() {
		$id = $this->PDOStatement->rowCount();
		return $id;
	}

	/**
	 * 返回操作结果：查找多行记录
	 * @return array  返回多维数组
	 * @return false  查找失败时, 返回false
	 */
	public function getSelectResult() {
		$result = $this->PDOStatement->fetchAll($this->fetchStyle);
		return $result ?: false;
	}

	/**
	 * 返回操作结果：查找单行记录
	 * @return array  查找多个字段时，返回一维数组
	 * @return string 查找单个字段时，返回字符串
	 * @return false  查找失败时, 返回false
	 */
	public function getFindResult() {
		$result = $this->PDOStatement->fetch($this->fetchStyle);
		return (is_array($result) and count($result) == 1) ? reset($result) : $result;
	}

	/**
	 * 获取执行的SQL语句
	 * @return string  $realSQL为false时, 返回PDO->prepare方法使用的SQL语句 (可能含有占位符)
	 * @return string  $realSQL为true 时, 返回PDO->prepare方法使用的SQL语句 (不含占位符)
	 */
	public function sql($realSQL = true) {
		if (!$realSQL) return $this->lastSQL;

		$PDOValues = $this->PDOValues;
		array_walk($PDOValues, function(&$value) {
			$value = '\''.addslashes($value).'\'';
		});

		return str_replace(array_keys($PDOValues), array_values($PDOValues), $this->lastSQL);
	}

	/**
	 * 事务
	 */
	public function transaction(Closure $closure) {
		try {
			//连接主服务器
			$this->connect(true);

			//启动事务
			$this->masterConnection->beginTransaction();
			
			$result = call_user_func($closure);

			if ($result === false) {
				$this->masterConnection->rollBack();	//回滚事务
			} else {
				$this->masterConnection->commit();  	//提交事务
			}
		} catch(Exception $e) {
			$this->masterConnection->rollBack();
			throw $e;
		}
	}

	/**
	 * 分页
	 */
	public function paginate($query, $pageSize = 10) {
		//克隆 Query 对象, 用于查询记录总数
		$queryCount = clone $query;

		//查询记录总数
		$total = $this->find($queryCount->field(['count(*)' => 'total']));

		//获取页码
		if (isset($_REQUEST['page']) and $_REQUEST['page'] > 1) {
			$currentPageNum = (int) $_REQUEST['page'];
		} else {
			$currentPageNum = 1;
		}

		//获取列表
		$list = $this->select($query->limit(($currentPageNum-1)*$pageSize, $pageSize));

		$firstPageNum = 1;
		$endPageNum   = ceil($total / $pageSize);
		$prevPageNum  = max(($currentPageNum - 1), $firstPageNum);
		$nextPageNum  = min(($currentPageNum + 1), $endPageNum);
		
		//根据页码生成链接
		function build_url($pageNum) {
			$parseUrl = parse_url($_SERVER['REQUEST_URI']);

			if (isset($parseUrl['query'])) {
				foreach (explode('&', $parseUrl['query']) as $kv) {
					list($key, $value) = explode('=', $kv);
					$params[$key] = $value;
				}
			}

			$params['page'] = $pageNum;
			return $parseUrl['path'].'?'.http_build_query($params);
		}

		$page = "<ul class='pagination'>
					<li><a href=".build_url($firstPageNum)."><span><<</span></a></li>
					<li><a href=".build_url($prevPageNum)."><span>上一页</span></a></li>
					<li><a href=".build_url($nextPageNum)."><span>下一页</span></a></li>
					<li><a href=".build_url($endPageNum)."><span>>></span></a></li>
				</ul>";

		return ['list' => $list, 'page' => $page];
	}

	/**
	 * 数据源名称或叫做 DSN，包含了请求连接到数据库的信息
	 */
	abstract function getDSN(array $config);
}