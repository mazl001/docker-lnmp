<?php
return [
    /*
    |--------------------------------------------------------------------------
    | 自动加载的全局中间件
    |--------------------------------------------------------------------------
    */
    'global' => [
		Onion\Middleware\CheckForMaintenanceMode::class,
		Onion\Middleware\EncryptCookies::class,
        Onion\Middleware\AddQueuedCookiesToResponse::class,
		Onion\Middleware\StartSession::class,
		Onion\Middleware\VerifyCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | 路由中间件
    |--------------------------------------------------------------------------
    */
    'route' => [

    ]
];