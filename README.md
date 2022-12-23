## laravel swoole redis pool

Laravel package to provide openswoole redis pool integration, laravel redis pool cache and session driver.

```php
public $config = [
    'host' => '127.0.0.1',
    'port' => 6379,
    'auth' => '',
    'dbIndex' => 0,
    'poolSize' => 64,
    'read_timeout' => 0.0,
    'timeout' => 0.0,
    'retry_interval' => 100,
    'retry_times' => 3,
];
```

## install

`composer require farmani/laravel-openswoole-redis-pool`

## how to use
* step 1: make true you've got a right swoole environment
* step 2: add this new storage in your `config/cache.php` file `stores` section below `redis` array

```php
'redis_pool' => [
    'driver' => 'redis',
    'connection' => 'default',
],
```
* step 3: change your redis drive or session drive to `redis_pool` in your `.env` file , that is it
