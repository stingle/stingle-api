#!/bin/bash
source .env
docker compose -p $CONTAINER_NAME exec web bash -c "rm -fv /var/www/html/cache/stingle_cache/*"
docker compose -p $CONTAINER_NAME exec web bash -c "/var/www/html/cgi.php module=v2 page=tools subpage=deleteCache rmcache=1 memcache=1"