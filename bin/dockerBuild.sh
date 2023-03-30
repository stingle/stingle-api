#!/bin/bash
if [ $# -eq 0 ]
  then
    echo "dockerBuild.sh versionNumber"
    exit
fi

docker build -t stingle/stingle-api:latest -t stingle/stingle-api:$1 -f docker/web.Dockerfile --pull .
docker push -a stingle/stingle-api