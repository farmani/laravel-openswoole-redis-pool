<?php

namespace Farmani\OpenSwooleRedis;

use Farmani\OpenSwooleRedis\Exceptions\InvalidRedisConnectionException;
use Illuminate\Cache\CacheManager;
use Illuminate\Session\CacheBasedSessionHandler;
use Illuminate\Session\SessionManager;
use Illuminate\Support\ServiceProvider;

class OpenSwooleRedisServiceProvider extends ServiceProvider
{
    protected $defer = false;

    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(): void
    {
        $this->registerRedisPoolStore();
        $this->registerCache();
        $this->registerSession();
    }

    protected function registerRedisPoolStore()
    {
        $this->app->singleton(RedisPoolManager::class, function ($app) {
            $config = $app->make('config')->get('database.redis_pool', []);
            if (empty($config)) {
                throw new InvalidRedisConnectionException('redis_pool database configuration not found');
            }
            return new RedisPoolManager($config);
        });

        $this->app->alias(RedisPoolManager::class, 'redis_pool');
    }

    protected function registerSession()
    {
        $this->app->afterResolving('session', function (SessionManager $manager) {
            $manager->extend('redis_pool', function ($app) {
                return new CacheBasedSessionHandler($app['cache']->store('redis_pool'), config('session.lifetime'));
            });
        });
    }

    protected function registerCache()
    {
        $this->app->afterResolving('cache', function (CacheManager $manager) {
            $manager->extend('redis_pool', function ($app) use ($manager) {
                return $manager->repository(
                    new OpenSwooleRedisStore(
                        $app->make('redis_pool'),
                        config('cache.prefix'),
                        config("cache.stores.redis_pool.connection", "default")
                    )
                );
            });
        });
    }
}
