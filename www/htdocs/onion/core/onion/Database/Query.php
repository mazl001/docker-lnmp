<?php
namespace Onion\Database;

use PDO;
use Closure;
use Onion\Database\drivers\PDOConnection;


class Query {
    
    /**
     * 数据库连接
     */
    protected $connection;

    /**
     * SQL语句执行参数
     */
    protected $options = [];

    /**
     * PDO参数绑定
     */
    protected $PDOValues = [];

    /**
     * 不进行PDO参数绑定的值 例如: 表字段、mysql 函数、null、not null
     */
    protected $PDOValuesExcept = ['null', 'not null'];
    /**
     * 表前缀
     */
    protected $prefix;

    /*
    |--------------------------------------------------------------------------
    | 构造函数
    |--------------------------------------------------------------------------
    */
    public function __construct(PDOConnection $connection, $prefix = null) {
        $this->connection = $connection;
        $this->prefix     = $prefix;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定表名，支持以下格式:
    |--------------------------------------------------------------------------
    | string: table
    | string: database.table
    | string: database.table1 t1, database table2 as t2
    | array : [database.table1, database.table2]
    | array : [database.table1 => t1, database.table2 => t2]
    */
    public function table($table) {
        $this->options['table'] = $table;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定表表名，支持以下格式:
    |--------------------------------------------------------------------------
    | string: alias
    | array : [database.table1 => t1, database.table2 => t2]
    */    
    public function alias($alias) {
        $this->options['alias'] = $alias;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定服务器类型
    |--------------------------------------------------------------------------
    */
    public function master(bool $master = true) {
        $this->options['master'] = $master;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定DSITINCT
    |--------------------------------------------------------------------------
    */
    public function distinct(bool $distinct = true) {
        $this->options['distinct'] = $distinct;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定字段，支持以下格式：
    |--------------------------------------------------------------------------
    | string: id,name
    | string: id,name as nick
    | array : ['id', 'name']
    | array : ['id', 'name' => 'nick']
    */
    public function field($field) {
        $this->options['field'] = $field;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定LOW_PRIORITY、QUICK、IGNORE等关键字
    |--------------------------------------------------------------------------
    | string: LOW_PRIORITY...
    */
    public function extra(string $extra) {
        $this->options['extra'] = $extra;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定分区，支持以下格式:
    |--------------------------------------------------------------------------
    | string: partition_name, partition_name
    | array : [partition_name, partition_name]
    */
    public function partition($partition) {
        $this->options['partition'] = $partition;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定 ON DUPLICATE KEY UPDATE 后语句
    |--------------------------------------------------------------------------
    | array: [id = 'UpId', name = 'upName'];
    */
    public function duplicate(array $duplicate) {
        $this->options['duplicate'] = $duplicate;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定 事件名称
    |--------------------------------------------------------------------------
    */
    public function event(string $event) {
        $this->options['event'] = $event;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 对结果集进行分组
    |--------------------------------------------------------------------------
    */
    public function group($group) {
        $this->options['group'] = $group;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 对分组信息 进行条件筛选
    |--------------------------------------------------------------------------
    */
    public function having($having) {
        $this->options['having'] = $having;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 对SELECT操作加锁 
    |--------------------------------------------------------------------------
    | string: FOR UPDATE
    | string: LOCK IN SHARE MODE
    */
    public function lock(string $lock) {
        $this->options['lock'] = $lock;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定排序，支持以下格式
    |--------------------------------------------------------------------------
    | string: $field = 'id', $order = 'asc'
    | array : ['id' => 'asc', 'name' => 'desc']
    */
    public function order($field, $order = 'desc') {
        if (is_string($field)) {
            $this->options['order'] = [$field => $order];
        } else if (is_array($field)) {
            $this->options['order'] = $field;
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定查询行数
    |--------------------------------------------------------------------------
    */
    public function limit(int $offset, int $rows = null) {

        $this->options['limit'] = func_get_args();

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定 表连接
    |--------------------------------------------------------------------------
    | @param string|array $table     表名
    | @param string|array $conditon  条件
    | @param string       $type      连接类型
    */
    public function join($table, $condition, string $type = 'inner') {
        $this->options['join'] = [
            'table'        => $table,
            'type'         => $type,
            'condition'    => $condition,
        ];

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 指定 SQL语句是否执行
    | execute：false: 只是构造SQL语句，不会执行SQL语句
    |--------------------------------------------------------------------------
    */
    public function execute(bool $execute = true) {
        $this->options['execute'] = $execute;
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 数据库操作：增
    |--------------------------------------------------------------------------
    */
    public function insert($parameters = []) {
        if (!empty($parameters)) {
            $this->options['data'] = $parameters;
        } else {
            return false;
        }

        return $this->connection->insert($this);
    }

    /*
    |--------------------------------------------------------------------------
    | 数据库操作：删
    |--------------------------------------------------------------------------
    */
    public function delete($force = false) {
        //没有指定条件，不进行删除操作
        if (empty($this->getOptions('where')) && !$force) {
            return false;
        }

        return $this->connection->delete($this);
    } 

    /*
    |--------------------------------------------------------------------------
    | 数据库操作：改
    |--------------------------------------------------------------------------
    */
    public function update($parameters = []) {
        if (!empty($parameters)) {
            $this->options['data'] = $parameters;
        } else {
            return false;
        }

        return $this->connection->update($this);
    }

    /*
    |--------------------------------------------------------------------------
    | 数据库操作：查(返回多行记录)
    |--------------------------------------------------------------------------
    */
    public function select() {
        return $this->connection->select($this);
    }

    /*
    |--------------------------------------------------------------------------
    | 数据库操作：查(返回一行记录)
    |--------------------------------------------------------------------------
    */
    public function find() {
        return $this->connection->find($this);
    }

    /*
    |--------------------------------------------------------------------------
    | 指定查询条件
    |-------------------------------------------------------------------------- 
    | 标准条件数组形式如下, 如果条件数组不含逻辑运算符，默认使用and
    |
    | where([
    |   '_logic' => 'and',
    |
    |   'id' => 1,
    |
    |   ['status', '!=', 1],
    | ]);
    |   
    | 解析为： id = 1 and status != 1
    */
    public function where(...$parameters) {
        //条件为字符串时:
        if (is_string($parameters[0])) {

            //纯字符串形式： where('id = 1') 
            if (count($parameters) == 1) {
                $this->options['where'] = $parameters[0];

            //PDO参数绑定形式(手动): where('id = :id', [':id' => 1])
            } else if(is_array($parameters[1])) {
                $this->options['where'] = $parameters[0];
                array_walk($parameters[1], [$this, 'bindPDOValues']);

            //PDO参数绑定形式(自动): where('id', 1)
            } else {
                $this->options['where'] = _and([$parameters]);
            }
        //条件为数组时:
        } else if (is_array($parameters[0])) {
            $this->options['where'] = !isset($parameters[0]['_logic']) ? _and($parameters[0]) : $parameters[0];
        }

        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 获取SQL语句执行参数
    |--------------------------------------------------------------------------
    */
    public function getOptions($name = null) {
        if (is_null($name)) {
            return $this->options;
        }

        return $this->options[$name] ?? null;
    }

    /*
    |--------------------------------------------------------------------------
    | PDO预处理SQL语句值绑定
    |--------------------------------------------------------------------------
    */
    public function bindPDOValues($value, $parameter = null) {
        //排除不进行PDO值绑定的值
        if (in_array(strtolower($value), array_map('strtolower', $this->PDOValuesExcept))) {
            return $value;
        }

        if(empty($parameter)) {
            $parameter = ':PDOValues_' . (count($this->PDOValues) + 1) . '_' . mt_rand();
        }

        $this->PDOValues[$parameter] = $value;
        return $parameter;
    }

    /*
    |--------------------------------------------------------------------------
    | 不进行PDO值绑定的值
    |--------------------------------------------------------------------------
    */
    public function bindExcept($value) {
        $this->PDOValuesExcept = array_merge($this->PDOValuesExcept, (array) $value);
        return $this;
    }

    /*
    |--------------------------------------------------------------------------
    | 获取PDO预处理SQL语句参数
    |--------------------------------------------------------------------------
    */
    public function getPDOValues() {
        return $this->PDOValues;
    }

    /*
    |--------------------------------------------------------------------------
    | 获取执行的SQL语句
    |--------------------------------------------------------------------------
    */
    public function sql($realSQL = true) {
        return $this->connection->sql($realSQL);
    }

    /*
    |--------------------------------------------------------------------------
    | 获取数据表前缀
    |--------------------------------------------------------------------------
    */
    public function getTablePrefix() {
        return $this->prefix;
    }

    /*
    |--------------------------------------------------------------------------
    | 事务
    |--------------------------------------------------------------------------
    */
    public function transaction(Closure $closure) {
        return $this->connection->transaction($closure);
    }

    /*
    |--------------------------------------------------------------------------
    | 分页
    |--------------------------------------------------------------------------
    */
    public function paginate($pageSize = 10) {
        return $this->connection->paginate($this, $pageSize);
    }
}