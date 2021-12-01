<?php
namespace Onion\Facades;

class Session extends Facade {
	public static function getFacadeAccessor() {
		return 'session';
	}
}