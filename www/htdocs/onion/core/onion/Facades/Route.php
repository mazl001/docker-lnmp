<?php
namespace Onion\Facades;

class Route extends Facade {
	public static function getFacadeAccessor() {
		return 'router';
	}
}