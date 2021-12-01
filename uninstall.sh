#!/bin/bash

ubuntu_uninstall_docker_engine() {
    apt-get autoremove --purge docker-engine
    rm -rf /var/lib/docker
}

centos_uninstall_docker_engine() {
    yum list installed | grep docker
    sudo yum -y remove docker-engine.x86_64
    rm -rf /var/lib/docker
}

issue=`cat /etc/issue`
if [[ "$issue" =~ "Ubuntu" ]]; then
    ubuntu_uninstall_docker_engine
elif [[ "$issue" =~ "CentOS" ]]; then
    centos_uninstall_docker_engine
else
    echo "脚本不支持当前操作系统"
    exit 1
fi

rm /usr/local/bin/docker-compose
