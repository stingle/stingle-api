#!/bin/bash

# Check if Docker is installed
if ! [ -x "$(command -v docker)" ]; then
  echo 'Docker is not installed.'

  # Check the Linux distribution
  if [ -f /etc/os-release ]; then
    # shellcheck disable=SC1091
    source /etc/os-release
    case "$ID" in
    ubuntu | debian)
      echo 'Installing Docker on Ubuntu or Debian...'
      # Add the Docker repository
      apt-get update
      apt-get install -y ca-certificates curl gnupg lsb-release
      mkdir -p /etc/apt/keyrings
      curl -fsSL https://download.docker.com/linux/$ID/gpg | gpg --dearmor -o /etc/apt/keyrings/docker.gpg
      echo \
        "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.gpg] https://download.docker.com/linux/$ID \
        $(lsb_release -cs) stable" | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null
      apt-get update
      apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
      ;;
    centos | rhel)
      echo 'Installing Docker on CentOS or Red Hat Enterprise Linux...'
      # Add the Docker repository
      yum install -y yum-utils
      yum-config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
      # Install Docker
      yum install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
      systemctl start docker
      systemctl enable docker
      ;;
    rocky)
      echo 'Installing Docker on Fedora...'
      # Add the Docker repository
      dnf -y install dnf-plugins-core
      dnf config-manager --add-repo https://download.docker.com/linux/centos/docker-ce.repo
      # Install Docker
      dnf -y install docker-ce docker-ce-cli containerd.io docker-compose-plugin
      systemctl start docker
      systemctl enable docker
      ;;
    fedora)
      echo 'Installing Docker on Fedora...'
      # Add the Docker repository
      dnf -y install dnf-plugins-core
      dnf config-manager --add-repo https://download.docker.com/linux/fedora/docker-ce.repo
      # Install Docker
      dnf -y install docker-ce docker-ce-cli containerd.io docker-compose-plugin
      systemctl start docker
      systemctl enable docker
      ;;
    *)
      echo 'Your distribution is not supported.'
      exit 1
      ;;
    esac
  else
    echo 'Your distribution is not supported.'
    exit 1
  fi
else
  echo 'Docker is already installed.'
fi

ENV_FILE_LOCATION=./.env
if [ ! -f $ENV_FILE_LOCATION ]; then
  echo -e "Creating new .env file"

    MYSQL_ROOT_PASSWORD=$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32 ; echo)
    MYSQL_USER_PASSWORD=$(LC_ALL=C tr -dc 'A-Za-z0-9' </dev/urandom | head -c 32 ; echo)
    CONTAINER_NAME=stingle-api
    COMPOSE_PARAMS=

    echo -e "MYSQL_ROOT_PASSWORD=\"$MYSQL_ROOT_PASSWORD\"" >> $ENV_FILE_LOCATION
    echo -e "MYSQL_USER_PASSWORD=\"$MYSQL_USER_PASSWORD\"" >> $ENV_FILE_LOCATION
    echo -e "CONTAINER_NAME=\"$CONTAINER_NAME\"" >> $ENV_FILE_LOCATION
    echo -e "COMPOSE_PARAMS=" >> $ENV_FILE_LOCATION
else
  source $ENV_FILE_LOCATION
fi

chmod +x bin/setup.php
chmod -R 777 cache
docker compose -p $CONTAINER_NAME up -d
docker exec -it $CONTAINER_NAME"-web-1" bash -c "cd /var/www/html/ && composer install --no-interaction"
docker exec -it $CONTAINER_NAME"-web-1" bash -c "/var/www/html/bin/setup.php --full --mysqlPass=\"$MYSQL_USER_PASSWORD\""
