#!/bin/bash
source .env
bin/dockerExec.sh "cd /var/www/html/ && ./bin/setup.php $@"