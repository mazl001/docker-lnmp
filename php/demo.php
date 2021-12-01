<?php
namespace docker;

function mysql() {
    $server = getenv('MYSQL_PORT_3306_TCP_ADDR');
    $user = getenv('MYSQL_ENV_MYSQL_USER');
    $password = getenv('MYSQL_ENV_MYSQL_ROOT_PASSWORD');
        
    $conn = mysql_connect($server, $user, $password);
    if ($conn) {
        echo '[mysql] Connected successfully<br>';
    } else {
        echo '[mysql] Could not connect: ' . mysql_error().'<br>';
    }
}

function memcache() {
    $mem = new \Memcache;
    $conn = $mem->connect(getenv('MEMCACHE_PORT_11211_TCP_ADDR'), getenv('MEMCACHE_PORT_11211_TCP_PORT'));
    if ($conn) {
        echo '[memcache] Connected successfully<br>';
    } else {
        echo '[memcache] Could not connect<br>';
    }
}

function redis() {
    $redis = new \Redis();
    $conn = $redis->connect(getenv('REDIS_PORT_6379_TCP_ADDR'), getenv('REDIS_PORT_6379_TCP_PORT'));
    if ($conn) {
        echo '[redis] Connected successfully<br>';
    } else {
        echo '[redis] Could not connect<br>';
    }
}

function rabbitmq() {
    $rabbitmq = new \AMQPConnection(array(
            'host'     => getenv('RABBITMQ_PORT_5672_TCP_ADDR'),
            'port'     => '5672',
            'vhost'    => '/',
            'login'    => 'guest',
            'password' => 'guest',
    ));

    $conn = $rabbitmq->connect();
    if ($conn) {
        echo '[rabbitmq] Connected successfully<br>';
    } else {
        echo '[rabbitmq] Could not connect<br>';
    }
}

mysql();
memcache();
redis();
rabbitmq();
