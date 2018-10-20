# swoole-test

Test Swoole Coroutine feature

## Set up

```php
$ composer install
$ docker-compose up -d
```

## How it works

Swoole Http Server runs with one worker only.

If you hit the server with '/?reg_num=<even_number>' it will perform a query that sleeps for 10 seconds.
This emulates a very slow query.

## Mongo DB

One Swoole server listens on port `8080`. It uses mongoDB to perform the slow query.
Hitting the server with:

- `http://127.0.0.1:8080/?reg_num=2` takes ~10s due to simulated slow query
- `http://127.0.0.1:8080/?reg_num=1` Without another active (and blocking) request it is fast, but
if a request with a slow query is active then this request is blocked until the other one is finished

## PDO MySQL

Second Swoole server listens on port `8081`. It uses PDO to perform the slow query.
There are two db backends available: Postgres and MySql
In the `server-pdo.php` file you can set `$useMySql = false;` to let the server use Postgres
instead of MySql.

Hitting the server with:

- `http://127.0.0.1:8081/?reg_num=2` takes ~10s due to simulated slow query
- `http://127.0.0.1:8081/?reg_num=1`
    - MySql: Request is not blocked by other requests, due to Coroutine feature
    - Postgres: Coroutine feature does not work, so behaviour is similar to MongoDB scenario