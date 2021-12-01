<?php
nameSpace Onion\Database\builder;

use Onion\Database\Query;

/**
 * 构建SQL语句
 */
class Mysql extends Builder {
	
	/**
	 * 常量: 一个空格
	 */
	const Space = ' ';

	/**
     * MySQL INSERT语句模板
	 * @see https://dev.mysql.com/doc/refman/5.7/en/delete.html
	 */
	protected $insertSQL = "INSERT[EXTRA] INTO[TABLE][PARTITION][VALUES][DUPLICATE KEY]";

	/**
     * MySQL DELETE语句模板
	 * @see https://dev.mysql.com/doc/refman/5.7/en/delete.html
	 */
	protected $deleteSQL = "DELETE[EXTRA] FROM[TABLE][PARTITION][WHERE][ORDER BY][LIMIT]";

	/**
     * MySQL SELECT语句模板
	 * @see https://dev.mysql.com/doc/refman/5.7/en/select.html
	 */
	protected $selectSQL = "SELECT[DISTINCT][EXTRA] [FIELD] FROM[TABLE][PARTITION][JOIN][WHERE][GROUP BY][HAVING][ORDER BY][LIMIT][LOCK]";

	/**
     * MySQL UPDATE语句模板
	 * @see https://dev.mysql.com/doc/refman/5.7/en/update.html
	 */
	protected $updateSQL = "UPDATE[EXTRA][TABLE] SET[VALUES][WHERE][ORDER BY][LIMIT]";

    /*
    |--------------------------------------------------------------------------
    | 构建 insert 语句
    |--------------------------------------------------------------------------
    */
	public function buildInsertSQL() {
		$searches = ['[EXTRA]', '[TABLE]', '[PARTITION]', '[VALUES]', '[DUPLICATE KEY]'];
		$replaces = [
				$this->parseExtra($this->getOptions('extra')), 
				$this->parseTable($this->getOptions('table'), $this->getOptions('alias'), $this->query->getTablePrefix()), 
				$this->parsePartition($this->getOptions('partition')), 
				$this->parseInsertData($this->getOptions('data')), 
				$this->parseDuplicate($this->getOptions('duplicate'))
			];

		return str_replace($searches, $replaces, $this->insertSQL);
	}

    /*
    |--------------------------------------------------------------------------
    | 构建 delete 语句
    |--------------------------------------------------------------------------
    */
	public function buildDeleteSQL() {
		$searches = ['[EXTRA]', '[TABLE]', '[PARTITION]', '[WHERE]', '[ORDER BY]', '[LIMIT]'];
		$replaces = [
			$this->parseExtra($this->getOptions('extra')), 
			$this->parseTable($this->getOptions('table'), $this->getOptions('alias'), $this->query->getTablePrefix()),  
			$this->parsePartition($this->getOptions('partition')), 
			$this->parseWhere($this->getOptions('where')),
			$this->parseOrder($this->getOptions('order')),
			$this->parseLimit($this->getOptions('limit'))
		];

		return str_replace($searches, $replaces, $this->deleteSQL);
	}

    /*
    |--------------------------------------------------------------------------
    | 构建 update 语句
    |--------------------------------------------------------------------------
    */
	public function buildUpdateSQL() {
		$searches = ['[EXTRA]', '[TABLE]', '[VALUES]', '[WHERE]', '[ORDER BY]', '[LIMIT]'];
		$replaces = [
			$this->parseExtra($this->getOptions('extra')), 
			$this->parseTable($this->getOptions('table'), $this->getOptions('alias'),$this->query->getTablePrefix()),  
			$this->parseUpdateData($this->getOptions('data')),
			$this->parseWhere($this->getOptions('where')),
			$this->parseOrder($this->getOptions('order')),
			$this->parseLimit($this->getOptions('limit'))
		];

		return str_replace($searches, $replaces, $this->updateSQL);
	}

    /*
    |--------------------------------------------------------------------------
    | 构建 select 语句
    |--------------------------------------------------------------------------
    */
	public function buildSelectSQL() {
		$searches = ['[DISTINCT]', '[EXTRA]', '[FIELD]', '[TABLE]', '[PARTITION]', '[JOIN]', '[WHERE]',
			'[GROUP BY]', '[HAVING]', '[ORDER BY]', '[LIMIT]', '[LOCK]'];

		$replaces = [
			$this->parseDistinct($this->getOptions('distinct')),
			$this->parseExtra($this->getOptions('extra')), 
			$this->parseField($this->getOptions('field')),
			$this->parseTable($this->getOptions('table'), $this->getOptions('alias'), $this->query->getTablePrefix()),  
			$this->parsePartition($this->getOptions('partition')),
			$this->parseJoin($this->getOptions('join')),
			$this->parseWhere($this->getOptions('where')),
			$this->parseGroup($this->getOptions('group')),
			$this->parseHaving($this->getOptions('having')),
			$this->parseOrder($this->getOptions('order')),
			$this->parseLimit($this->getOptions('limit')),
			$this->parseLock($this->getOptions('lock'))
		];

		return str_replace($searches, $replaces, $this->selectSQL);
	}


    /*
    |--------------------------------------------------------------------------
    | 解析 表名
    |--------------------------------------------------------------------------
    */
	public function parseTable($table, $alias, $prefix = null) {
		//判断是否设置表别名
		if (is_string($alias)) {
			$table = [$table => $alias];
		} else if(is_array($alias)) {
			$table = $alias;
		}

		return $this->parseTableOrField($table, $prefix);
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 字段
    |--------------------------------------------------------------------------
    */
	public function parseField($field) {
		$field = !empty($field) ? $field : '*';
		return $this->parseTableOrField($field);
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 表名 或 字段名
    |--------------------------------------------------------------------------
	| string: table
	| string: database.table
	| string: database.table1 as t1, database.table2 as t2
	| array : ['database.table1', 'database.table2']
	| array : ['database.table1' => 't1', 'database.table2' => 't2']
	| 注意  ： 字段含有函数时，不添加转义符号, 例如: 
	|         count(id)、count(distinct id) as total
    */
	public function parseTableOrField($parameters, $prefix = null) {
		//如果参数是字符串类型，统一转化为数组类型: ['database.table1' => 't1', 'database.table2']
		if (is_string($parameters)) {
			$items = array_map('trim', explode(',', $parameters));

			$parameters = [];
			foreach($items as $value) {
				//判断是否包含别名
				if (preg_match('/\s+(as)*\s*/i', $value)) {
					list($name, $alias) = preg_split('/\s+(as)*\s*/i', $value);
					$parameters[$name] = $alias;
				} else {
					$parameters[] = $value;
				}
			}
		}

        //数组的 值 统一形式为： `database`.`table1` as `t1`
        array_walk($parameters, function(&$value, $key) use ($prefix) {
            if (is_numeric($key)) {
            	$value = $this->formatKeyword($value, $prefix);
        	} else {
        		$value = $this->formatKeyword($key, $prefix). ' as '.$this->formatKeyword($value);
        	}
        });

        $parameters = implode(',', $parameters);
        return self::Space.$parameters;
	}

    /*
    |--------------------------------------------------------------------------
    | 解析额外关键词
    |--------------------------------------------------------------------------
    */
	public function parseExtra($extra) {
		return $extra ? self::Space.strtoupper($extra) : '';
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 DISTINCT
    |--------------------------------------------------------------------------
    */
	public function parseDistinct($distinct) {
		return $distinct ? ' DISTINCT' : '';
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 PARTITION，支持以下格式：
    |--------------------------------------------------------------------------
	| string: partition_name, partition_name
	| array : [partition_name, partition_name]
    */
	public function parsePartition($partition) {
		if (is_array($partition)) {
			$partition = implode(',', $partition);
		}

		return $partition ? sprintf(' PARTITION (%s)', $partition) : '';
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 DUPLICATE KEY，支持以下格式
    |--------------------------------------------------------------------------
	| array: [id = 'UpId', name = 'upName'];
    */
	public function parseDuplicate($duplicate) {
		 //数组的值统一形式为： id = UpId
		if (is_array($duplicate)) {
			array_walk($duplicate, function(&$value, $field) {
				$value = $this->formatKeyword($field) .' = '. $this->query->bindPDOValues($value);
			});

			$duplicate = implode(',', $duplicate);
		}

 		return $duplicate ? ' ON DUPLICATE KEY UPDATE '.$duplicate : '';
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 GROUP BY
    |--------------------------------------------------------------------------
    */
	public function parseGroup($group) {
		return $group ? ' GROUP BY '.$this->formatKeyword($group) : ''; 
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 HAVING
    |--------------------------------------------------------------------------
    */
	public function parseHaving($having) {
		return $having ? ' HAVING '.$having : '';
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 ORDER BY
    |--------------------------------------------------------------------------
	| array: ['id' => 'asc', 'name' => 'desc']
    */
	public function parseOrder($order) {
		 //数组的值统一形式为： id asc
		if (is_array($order)) {
			array_walk($order, function(&$value, $key) {
				$value = $this->formatKeyword($key). ' '.$value;
			});
		}

		return $order ? ' ORDER BY '.implode(',', $order) : '';
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 LIMIT
    |--------------------------------------------------------------------------
    */
	public function parseLimit($limit) {
		return $limit ? ' LIMIT '.implode(',', $limit) : '';
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 JOIN
    |--------------------------------------------------------------------------
    */
	public function parseJoin($join) {
		if (!empty($join)) {
			$table = $this->parseTable($join['table'], null, $this->query->getTablePrefix());
			return sprintf(' %s JOIN %s ON %s', $join['type'], $table, $join['condition']);
		}
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 Lock
    |--------------------------------------------------------------------------
    */
	public function parseLock($lock) {
		return $lock ? self::Space.$lock : '';
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 insert 语句 数据
    |--------------------------------------------------------------------------
    */
	public function parseInsertData(array $parameters) {
		//如果数组是不是一维数组，统一转化为二维数组 (兼容批量插入数据)
		if (_isOneDimensionalArray($parameters)) {
			$parameters = [$parameters];
		}

		foreach ($parameters as $parameter) {
			$fields = [];
			$placeholder = [];

			foreach ($parameter as $field => $value) {
				$fields[] = $this->formatKeyword($field);
				$placeholder[] = $this->query->bindPDOValues($value);
			}

			$values[] = sprintf('(%s)', implode(',', $placeholder));
		}

		return sprintf(' (%s) VALUES %s', implode(',', $fields), implode(',', $values));
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 update 语句 数据
    |--------------------------------------------------------------------------
    */
	public function parseUpdateData(array $parameters) {
		foreach ($parameters as $field => $value) {
			$values[] = $this->formatKeyword($field) . '='. $this->query->bindPDOValues($value);		
		}

		return self::Space.implode(',', $values);
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 WHERE
    |--------------------------------------------------------------------------
    */
	public function parseWhere($map) {
		if (is_array($map)) {
			$map = $this->parseArrayWhere($map);
		}

		return $map ? ' WHERE '.$map : '';
	}

    /*
    |--------------------------------------------------------------------------
    | 解析 WHERE (数组形式参数)
    |--------------------------------------------------------------------------
    |
	| parseArrayWhere([
	|		'id'	=>	1,
	|		'status'=>	0,
	|
	|		[
	|			['name', 'stephen'],
	|			['name', 'peter'],
	|			'_logic'	=>	'or'
	|		],
	|
	|		'_logic'	=>	'and'
	| 	]);
    */
	public function parseArrayWhere(array $conditions) {

		foreach ($conditions as $key => $condition) {

			//如果条件包含逻辑运算符, 递归地解析该子数组
			if (is_array($condition) and isset($condition['_logic'])) {
				$result[] = $this->parseArrayWhere($condition);

			//条件为数组时, 例如：['status', '!=', 1]
			} elseif (is_array($condition)) {
				$result[] = implode(self::Space, $this->parseCondition($condition));
			
			//条件为 键=>值 时, 例如：'id' => 1
			} elseif ($key !== '_logic') {
				$result[] = implode(self::Space, $this->parseCondition([$key => $condition]));
			}
		}

		return sprintf('(%s)', implode(self::Space.$conditions['_logic'].self::Space, $result));
	}


    /*
    |--------------------------------------------------------------------------
    | 解析 条件, PDO 参数绑定
    |--------------------------------------------------------------------------
    | 标准条件 形式如下, 其他形式的参数都会转化为标准形式
    | $condition = ['field', 'operator', 'value']
    | 
    */
    public function parseCondition(array $condition) {
    	//关联数组形式: parseCondition(['id' => 1]) 转化为标准形式
        if (count($condition) == 1) {
            $condition = [key($condition), '=', current($condition)];
        //数值数组，且省略等号形式: parseCondition(['id', 1]) 转化为标准形式
        } else if (count($condition) == 2) {
            $condition = [$condition[0], '=', $condition[1]];
        }

        list($field, $operator, $values) = $condition;

	    /*
	    |--------------------------------------------------------------------------
	    | PDO 参数绑定
	    |--------------------------------------------------------------------------
	    | 简单查询:    parseCondition(['id', '=', 1])
	    | like查询:    parseCondition(['id', 'like', '%stephen%'])
	    | regexp查询:  parseCondition(['id', 'regexp', '[0-9]')
	    |
	    | in查询:      parseCondition(['id', 'in', [1, 2, 3]])
	    | between查询: parseCondition(['id', 'between', [1, 5]])
	    | null查询:    parseCondition(['id', 'is', 'not null'])
	    | 
	    */

		foreach ((array)$values as $value) {
    		$PDOParameters[] = $this->query->bindPDOValues($value);
    	}

	    switch ($operator) {
	    	case 'in':
	        	return [$this->formatKeyword($field), $operator, '('. implode(',', $PDOParameters). ')'];
	    	case 'between':
	        	return [$this->formatKeyword($field), $operator, implode(' AND ', $PDOParameters)];
	    	default:
		    	return [$this->formatKeyword($field), $operator, implode($PDOParameters)];	    	
	    }
    }

    /*
    |--------------------------------------------------------------------------
    | 表名、字段名 添加 转义符号、表前缀, 支持以下格式：
    |--------------------------------------------------------------------------
	| string: table
	| string: database.table
    */
	public function formatKeyword(string $keyword, $prefix = null) {
		//字段是mysql函数, 不添加转义符号, 例如: sum(id)、count(id)
		if ($this->containFunction($keyword)) return $keyword;

		//将字符串转化为数组
		if (strpos($keyword, '.') !== false) {
			list($database, $table) = explode('.', $keyword);
			$keyword = [$database, $prefix.$table];
		} else {
			$keyword = [$prefix.$keyword];
		}

		//对数组中的每个元素添加转义符号
		array_walk($keyword, function(&$value) {
			if (strpos($value, '`') === false and trim($value) !== '*') {
				$value = '`'.$value.'`';
			}		
		});

		return implode('.', $keyword);
	}

    /*
    |--------------------------------------------------------------------------
    | 判断字段是否是包含函数, 支持以下格式：
    |--------------------------------------------------------------------------
    | string: count(id), count(distinct id)
    */
    public function containFunction($keyword) {
        return strpos($keyword, '(') !== false and strpos($keyword, ')') !== false 
        and substr(trim($keyword), '-1') === ')';
    }
}