#!/bin/bash
source .env
docker exec -it $CONTAINER_NAME"-web-1" bash -c "rm -fv /var/www/html/cache/stingle_cache/*"
docker exec -it $CONTAINER_NAME"-web-1" bash -c "/var/www/html/cgi.php module=v2 page=tools subpage=deleteCache rmcache=1 memcache=1"