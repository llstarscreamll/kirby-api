# Kirby API

[![Build Status](https://travis-ci.com/llstarscreamll/kirby-api.svg?branch=master)](https://travis-ci.com/llstarscreamll/kirby-api)
[![StyleCI](https://github.styleci.io/repos/171598863/shield?branch=master)](https://github.styleci.io/repos/171598863)
[![codecov](https://codecov.io/gh/llstarscreamll/kirby-api/branch/master/graph/badge.svg)](https://codecov.io/gh/llstarscreamll/laravel)
![GitHub](https://img.shields.io/github/license/llstarscreamll/kirby-api?logo=github)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/llstarscreamll/kirby-api/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/llstarscreamll/kirby-api/?branch=master)
[![Code Coverage](https://scrutinizer-ci.com/g/llstarscreamll/kirby-api/badges/coverage.png?b=master)](https://scrutinizer-ci.com/g/llstarscreamll/kirby-api/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/llstarscreamll/kirby-api/badges/build.png?b=master)](https://scrutinizer-ci.com/g/llstarscreamll/kirby-api/build-status/master)

This project aims to be as a base for reutilizable Laravel packages used for multipurpose stuff. The laravel libraries are located on the `packages` folder under de **Kirby** vendor name. Here are some things this package currently does:

- Authentication API
- Employees API
- Employees Time Clock API
- Work shifts API
- Employee Novelties API
- Novelty types API
- Company API
- Users API

## Install

```bash
# setup mysql
brew install mysql
brew services start mysql
mysql -u root -e "CREATE USER 'homestead'@'localhost' IDENTIFIED BY 'secret';"
mysql -u root -e "GRANT ALL PRIVILEGES ON *.* TO 'homestead'@'localhost' WITH GRANT OPTION;"
mysql -u root -e "CREATE DATABASE IF NOT EXISTS test"
mysql -u root -e "CREATE DATABASE IF NOT EXISTS kirby_local"

git clone https://github.com/llstarscreamll/kirby-api.git
cd kirby-api
composer install
cp .env.example .env # fill the environment variables
php artisan migrate --seed
php artisan passport:install # fill the environment variables based on output
php artisan company:sync-holidays # use the --nex-year flag to sync next year holidays
```

## Deploying

Make sure to have your `.env` file, server and Github `ssh` keys correctly established on your local and remote instances. To make a full deploy execute:

```bash
envoy run deploy --target=prod
```

To deploy only the code:

```bash
envoy run deployOnlyCode
```

If you want to deploy to specific server and branch execute:

```bash
envoy run deploy --target=lab --branch=staging
```

To use `--target=lab` flag you must have in your environment `LAB_SERVERS`. Example:

```
# separate servers with semicolons
LAB_SERVERS="john_doe@1.2.3.4;john_doe@5.6.7.8"
```

### Setup Redis on host machine

Add Docker `host.docker.internal` (172.17.0.1) to the redis bind directive on `/etc/redis/redis.conf` file:

```
bind 127.0.0.1 -::1 172.17.0.1
```

Said address is shown from `ifconfig` command:

```
ifconfig
docker0: flags=4099<UP,BROADCAST,MULTICAST>  mtu 1500
        inet 172.17.0.1  netmask 255.255.0.0  broadcast 172.17.255.255
```

### Set Firewall options on production server

Configure the Linux Firewall to allow https connections on ports 8000 and 4200:

```bash
# Add a custom service for HTTPS on port 8000
sudo firewall-cmd --permanent --new-service=https-8000

# Configure the custom service
sudo firewall-cmd --permanent --service=https-8000 --add-port=8000/tcp
sudo firewall-cmd --permanent --service=https-8000 --set-short="HTTPS on port 8000"
sudo firewall-cmd --permanent --service=https-8000 --set-description="Allow HTTPS traffic on port 8000"

# Add the service to the public zone
sudo firewall-cmd --permanent --zone=public --add-service=https-8000

# Reload firewalld to apply changes
sudo firewall-cmd --reload

# Verify the service is active
sudo firewall-cmd --list-all
```