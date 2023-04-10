#!/bin/bash
source .env
docker compose -p $CONTAINER_NAME exec web bash -c "$@"