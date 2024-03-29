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
