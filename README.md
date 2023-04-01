# Stingle API Server

## Prerequisites
* Debian, Ubuntu, RHEL, CentOS, Rocky, Fedora linux, MacOS or Windows WSL
* S3 compatible storage
* Domain name

## Build
```bash
./setup.sh
```
This will install docker if it's not present on the system and then start building docker image. After image is built it will start docker compose. When all containers are up it will start a setup process. Answer several questions and Stingle API server will be ready.

By default project files  will be mounted into the container. This is useful for development puposes.
If you would like to deploy Stingle API server for production use, please refer to: https://github.com/stingle/stingle-api-docker project.

`setup.sh` script will create config file in configsSite folder. Please backup config.override.inc.php in a safe place, in case you want to redeploy server later.

### Setup script
Let's say something is changed on the server and you want to modify your configuration. You can re-run setup script but partially:

```bash
./bin/setup-internal.sh
```
Possible option for the setup script
```
Setup script of Stingle API server
Usage:
--full                  Run full setup
--mysqlPass             MySQL password
--mysql                 MySQL setup
--systemKeys            Generate system keys
--storage               S3 storage configuration
--backup                Backup configuration
--hostname              Set a hostname
--backup-cron           Install cronjob for backups
--rm-backup-cron        Remove cronjob for backups
--update-addons-cron    Update cronjobs from addons
--rm-cron               Remove given addon's cron file
-h --help               Display this help message
```


## Addons
Project has addons support. You can write new addon without touching main code. Just place addon folder into `addons/` folder and project will pick it up automatically. Addons can bring their own packages, configs, controllers, views and composer dependencies.

### Structure of an addon
```
addons/addon-test
|___ bin
|___ configs
|___ controllers
|___ incs
|___ packages
|___ packages
|___ view
|___ composer.json
|___ crontab
|___ init.inc.php
```