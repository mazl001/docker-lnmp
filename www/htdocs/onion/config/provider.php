<?php
return [

    /*
    |--------------------------------------------------------------------------
    | 框架自动加载的服务提供者类
    |--------------------------------------------------------------------------
    */
    Onion\Providers\EventServiceProvider::class,
    Onion\Providers\CookieServiceProvider::class,
	Onion\Providers\DatabaseServiceProvider::class,
	Onion\Providers\EncryptionServiceProvider::class,
	Onion\Providers\RedisServiceProvider::class,
	Onion\Providers\RouteServiceProvider::class,
	Onion\Providers\SessionServiceProvider::class,
	Onion\Providers\ViewServiceProvider::class,
];