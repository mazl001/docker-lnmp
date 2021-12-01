<?php
namespace Onion\Database\drivers;

class Mysql extends PDOConnection {

	/**
	 * 数据源名称或叫做 DSN，包含了请求连接到数据库的信息
	 */
	public function getDSN(array $config) {

		if (!empty($config['socket'])) {
			$dsnConfig['unix_socket'] = $config['socket'];
		} else {
			$dsnConfig['host'] = $config['host'];
			$dsnConfig['port'] = $config['port'] ?? 3306;
		}

		$dsnConfig['dbname']  = $config['database'];
		$dsnConfig['charset'] = $config['charset'] ?? 'utf8';

		$dsn = 'mysql:';
		foreach(array_filter($dsnConfig) as $key => $value) {
			$dsn .= $key.'='.$value.';';
		}

		return $dsn;
	}
}