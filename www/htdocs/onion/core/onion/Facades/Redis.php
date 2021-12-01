<?php
namespace Onion\Facades;

class Redis extends Facade {
	public static function getFacadeAccessor() {
		return 'redis';
	}
}