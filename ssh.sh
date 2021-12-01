#!/bin/bash

if [ -z $1 ]; then
    container='php'
else
    container=$1
fi

docker exec -it ${container} bash
