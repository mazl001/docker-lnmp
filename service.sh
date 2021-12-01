#!/bin/bash

if [ -z $1 ]; then
    echo 'Usage: service.sh start|stop|restart'
    exit 0   
fi

case $1 in
start)
    if [ ! -d /www ];then
        mkdir -p /www/data/elasticsearch/
        mkdir -p /www/data/mysql/
        mkdir -p /www/data/rabbitmq/mnesia/rabbitmq/
        mkdir -p /www/log/nginx/
        mkdir -p /www/log/php/
        mkdir -p /www/htdocs/default/
        cp ./php/demo.php /www/htdocs/default/index.php
    fi        
   
    docker-compose kill
    docker-compose build
    docker-compose up -d
    docker-compose ps
    ;;
stop)
    docker-compose kill
    ;;
restart)
    docker-compose kill
    docker-compose build
    docker-compose up -d
    docker-compose ps
   ;; 
esac
