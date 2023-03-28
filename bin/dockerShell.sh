#!/bin/bash
docker exec -it stingle_api-web-1 bash
docker exec -it $CONTAINER_NAME"-web-1" bash -c "cd /var/www/html/ && composer install --ignore-platform-reqs --no-interaction"