## laravel swoole redis pool

Laravel package to provide openswoole redis pool integration, laravel redis pool cache and session driver.

change your default redis database configuration as follows:

```php
'default' => [
    'url' => env('REDIS_URL'),
    'host' => env('REDIS_HOST', 'redis.waptap.dev'),
    'password' => env('REDIS_PASSWORD', null),
    'port' => env('REDIS_PORT', '6379'),
    'database' => env('REDIS_DB', '0'),
    'read_timeout' => 3.0,
    'timeout' => 15.0,
    'retry_interval' => 0,
    'reserved' => '',
    'pool' => [
        'min' => 128,
        'max' => 128,
        'idle_time' => -1,
        'idle_interval' => 1000,
        'heartbeat' => 3000,
        'retry_interval' => 10,
        'retry_times' => 3,
    ],
],
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
