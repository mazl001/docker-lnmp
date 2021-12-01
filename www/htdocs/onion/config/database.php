<?php 
return [
    //默认数据连接标识
    'default'     => 'demo',
 
    //数据库连接信息
    'connections' => [
 
        //数据库配置信息
        'demo' => [
            //驱动类型 (目前只支持mysql)
            'driver'     => 'mysql',
             
            //表前缀
            'prefix'    =>  'onion_',
 
            //服务器连接参数 (各台服务器的参数相同时, 只需填写一个)
            'servers'   => [
                //服务器地址 
                'host'       => ['127.0.0.1'],
                //服务器端口
                'port'       => [3306],
                //数据库名 
                'database'   => ['demo'],
                //用户名
                'username'   => ['root'],
                //密码
                'password'   => [''],
                //数据库编码
                'charset'    => ['utf8']
            ]
 
 
            /**
             * 如果要通unix socket连接, 你需要修改配置为:
             *
             * 'servers'   => [
             *   //socket地址
             *   'socket'     => ['your_socket_path']
             *   //数据库名 
             *   'database'   => ['your_database'],
             *   //用户名
             *   'username'   => ['your_username'],
             *   //密码
             *   'password'   => ['your_password'],
             *   //数据库编码
             *   'charset'    => ['utf8']
             * ]
             */
        ]
    ]
];