# Laravel Kirby

[![Build Status](https://travis-ci.com/llstarscreamll/laravel.svg?branch=develop)](https://travis-ci.com/llstarscreamll/laravel)
[![StyleCI](https://github.styleci.io/repos/171598863/shield?branch=develop)](https://github.styleci.io/repos/171598863)
[![codecov](https://codecov.io/gh/llstarscreamll/laravel/branch/develop/graph/badge.svg)](https://codecov.io/gh/llstarscreamll/laravel)

this project aims to be as a base for Laravel packages development used for multipurpose stuff, since I'm tired to writing the same things over and over again. The laravel libraries are located on the packages folder under de llstarscreamll vendor name.

## Install

```bash
git clone https://github.com/llstarscreamll/laravel.git
cd laravel
composer install
cp .env.example .env # fill the environment variables
php artisan migrate --seed
php artisan passport:install # fill the environment variables based on output
php artisan company:sync-holidays # use the --nex-year flag to sync next year holidays
```

## Deploying

Make sure to have your `.env` file, server and Github `ssh` keys correctly established on your local and remote instances. To make a full deploy execute:

```bash
envoy run deploy
```

To deploy only the code:

```bash
envoy run deployOnlyCode
```

If you want to deploy to specific server and branch execute:

```bash
envoy run --target=lab --branch=staging
```

To use `--target=lab` flag you must have in your environment `LAB_SERVERS`. Example:

```
# set many servers separated by semicolon
LAB_SERVERS="john_doe@1.2.3.4;john_doe@5.6.7.8"
```

For lab servers, some times you need to reset the Postgres database:

```bash
sudo -u postgres psql -c "DROP SCHEMA public CASCADE;
create SCHEMA public;
grant usage on schema public to public;
grant create on schema public to public;" database_name
```

Replace `database_name`.
