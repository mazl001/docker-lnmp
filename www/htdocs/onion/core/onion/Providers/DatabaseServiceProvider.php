<?php
namespace Onion\Providers;

use Onion\Database\DatabaseManager;
use Onion\Database\Model;


/**
 * 服务提供者: 数据库
 */
class DatabaseServiceProvider extends ServiceProvider {
	
	public function register() {
		$this->application->singleton('db', function() {
            return new DatabaseManager($this->application->config['database'], $this->application->event);
        });
	}

	public function boot() {
		Model::setDatabaseManager($this->application->db);
	}
}