#!/bin/bash
source .env
docker exec -it $CONTAINER_NAME"-web-1" bash -c "cd /var/www/html/ && composer $@"