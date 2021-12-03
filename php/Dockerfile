FROM php:7.0-fpm

# Install env
#add sources.list    /etc/apt/sources.list
run apt-get update && apt-get install -y \
        librabbitmq-dev \
        libgearman-dev \
        libmemcached-dev \
        libmcrypt-dev \
        libfreetype6-dev \
        libjpeg62-turbo-dev \
        libpng-dev \
        libcurl4-gnutls-dev \
        vim \
	    #php7.0-cli \
	    tree \
    && apt-get clean \
    && apt-get autoclean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-ext-configure gd --with-freetype-dir=/usr/include/ --with-jpeg-dir=/usr/include/ && \
    docker-php-ext-install zip && \
    docker-php-ext-install gd && \
    docker-php-ext-install mbstring && \
    docker-php-ext-install mcrypt && \
    docker-php-ext-install pdo pdo_mysql && \
    docker-php-ext-install mysqli && \
    docker-php-ext-install bcmath && \
    docker-php-ext-install curl $$ \
    docker-php-ext-install sockets

#ADD https://pecl.php.net/get/memcached-2.2.0.tgz /usr/src/php/ext/memcached.tgz
#RUN tar -xf /usr/src/php/ext/memcached.tgz -C /usr/src/php/ext/ && \
#    rm /usr/src/php/ext/memcached.tgz && \
#    docker-php-ext-install memcached-2.2.0

ADD https://pecl.php.net/get/redis-5.0.1.tgz /usr/src/php/ext/redis.tgz
RUN tar -xf /usr/src/php/ext/redis.tgz -C /usr/src/php/ext/ && \
    rm /usr/src/php/ext/redis.tgz && \
    docker-php-ext-install redis-5.0.1

#ADD https://pecl.php.net/get/memcache-2.2.7.tgz /usr/src/php/ext/memcache.tgz
#RUN tar -xf /usr/src/php/ext/memcache.tgz -C /usr/src/php/ext/ && \
#    rm /usr/src/php/ext/memcache.tgz && \
#    docker-php-ext-install memcache-2.2.7

#ADD https://pecl.php.net/get/xdebug-2.3.2.tgz /usr/src/php/ext/xdebug.tgz
#RUN tar -xf /usr/src/php/ext/xdebug.tgz -C /usr/src/php/ext/ && \
#    rm /usr/src/php/ext/xdebug.tgz && \
#    docker-php-ext-install xdebug-2.3.2

#ADD https://pecl.php.net/get/amqp-1.4.0.tgz /usr/src/php/ext/amqp.tgz
#RUN tar -xf /usr/src/php/ext/amqp.tgz -C /usr/src/php/ext/ && \
#    rm /usr/src/php/ext/amqp.tgz && \
#    docker-php-ext-install amqp-1.4.0

#Composer
ADD /phar/composer.phar /usr/local/bin/composer
RUN chmod 755 /usr/local/bin/composer

#PHPUnit
ADD /phar/phpunit.phar /usr/local/bin/phpunit
RUN chmod 755 /usr/local/bin/phpunit 

#PHP config
ADD php.ini    /usr/local/etc/php/php.ini
ADD php-fpm.conf    /usr/local/etc/php-fpm.conf
