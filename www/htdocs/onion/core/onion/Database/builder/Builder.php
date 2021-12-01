<?php
namespace Onion\Database\builder;

use Onion\Database\Query;

abstract class Builder {

    /**
     * 构造方法
     */
	public function __construct(Query $query) {
		$this->query = $query;
	}

    /**
     * 获取SQL语句执行参数
     */
    public function getOptions($name = null) {
        return $this->query->getOptions($name);
    }


    /**
     * 构造 insert 语句
     */
	abstract public function buildInsertSQL();


    /**
     * 构造 delete 语句
     */
	abstract public function buildDeleteSQL();


    /**
     * 构造 update 语句
     */
	abstract public function buildUpdateSQL();


    /**
     * 构造 select 语句
     */
	abstract public function buildSelectSQL();

    /**
     * 构造 find 语句
     */
    public function buildFindSQL() {
        return $this->buildSelectSQL();
    }
}