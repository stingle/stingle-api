#!/bin/bash
source .env
docker exec -it $CONTAINER_NAME"-web-1" composer $@