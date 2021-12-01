#!/bin/bash

ubuntu_install_docker_engine() {
    #Update your apt sources
    sudo apt-get update
    sudo apt-get install apt-transport-https ca-certificates
    sudo apt-key adv --keyserver hkp://p80.pool.sks-keyservers.net:80 --recv-keys 58118E89F3A912897C070ADBF76221572C52609D
    
    dockerlist="/etc/apt/sources.list.d/docker.list"
    if [ ! -f $dockerlist ]; then
        touch $dockerlist
    fi
    
    lsb_release=`lsb_release -a`
    if [[ "$lsb_release" =~ "12.04"  ]]; then
        echo "deb https://apt.dockerproject.org/repo ubuntu-precise main" > $dockerlist
    elif [[ "$lsb_release" =~ "14.04"  ]]; then
        echo "deb https://apt.dockerproject.org/repo ubuntu-trusty main" > $dockerlist
    elif [[ "$lsb_release" =~ "15.10"  ]]; then
        echo "deb https://apt.dockerproject.org/repo ubuntu-wily main" > $dockerlist
    elif [[ "$lsb_release" =~ "16.04"  ]]; then
        echo "deb https://apt.dockerproject.org/repo ubuntu-xenial main" > $dockerlist
    else
        echo "不支持的UBUNTU版本"
        exit 1
    fi
    
    sudo apt-get purge lxc-docker
    apt-cache policy docker-engine
    
    #Prerequisites by Ubuntu Version
    if [[ "$lsb_release" =~ "12.04"  ]]; then
        apt-get install -y apparmor
    else
        sudo apt-get install -y linux-image-extra-$(uname -r)
    fi
    
    #Install
    sudo apt-get update
    sudo apt-get install -y docker-engine
    sudo service docker start
    sudo docker --version 
}

centos_install_docker_engine() {
    sudo yum update
    sudo tee /etc/yum.repos.d/docker.repo <<-'EOF'
[dockerrepo]
name=Docker Repository
baseurl=https://yum.dockerproject.org/repo/main/centos/$releasever/
enabled=1
gpgcheck=1
gpgkey=https://yum.dockerproject.org/gpg
EOF
    sudo yum install docker-engine
    sudo service docker start
    sudo docker --version
}

issue=`cat /etc/issue`
if [[ "$issue" =~ "Ubuntu" ]]; then
    ubuntu_install_docker_engine
elif [[ "$issue" =~ "CentOS" ]]; then
    centos_install_docker_engine
else
    echo "脚本不支持当前操作系统"
    exit 1
fi

#docker-compose
curl -L https://github.com/docker/compose/releases/download/1.7.1/docker-compose-`uname -s`-`uname -m` > /usr/local/bin/docker-compose
chmod +x /usr/local/bin/docker-compose
docker-compose --version

source ./service.sh start
