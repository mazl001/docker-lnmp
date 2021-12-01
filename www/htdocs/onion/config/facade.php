<?php
return [
    /*
    |--------------------------------------------------------------------------
    | 注册Facade类别名
    | 注册后，将不在需要手动 use Onion\Facades\Route;
    |--------------------------------------------------------------------------
    */

	'aliases'	=> 	[
		'App'		 =>	Onion\Facades\App::class,
		'Config'	 =>	Onion\Facades\Config::class,
        'Cookie'     => Onion\Facades\Cookie::class,
        'DB'         => Onion\Facades\DB::class,
        'Event'      => Onion\Facades\Event::class,
        'Encrypter'  => Onion\Facades\Encrypter::class,
		'Route'		 => Onion\Facades\Route::class,
        'Redis'      => Onion\Facades\Redis::class,
        'Session'    => Onion\Facades\Session::class
	]
];