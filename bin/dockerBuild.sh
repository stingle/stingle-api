#!/bin/bash
if [ $# -eq 0 ]
  then
    echo "dockerBuild.sh versionNumber"
    exit
fi

VERSION=$1
MAJOR_MINOR=$(echo "$VERSION" | cut -d '.' -f 1,2)

# Build the Docker image with the provided version number
docker build -t stingle/stingle-api:"$VERSION" -t stingle/stingle-api:latest -f docker/web.Dockerfile --pull .
exit_code=$?

if [ $exit_code -eq 0 ] && [ "$VERSION" != "$MAJOR_MINOR" ]; then
    docker tag stingle/stingle-api:"$VERSION" stingle/stingle-api:"$MAJOR_MINOR"
fi