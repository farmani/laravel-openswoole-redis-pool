<?php

namespace Farmani\OpenSwooleRedis;

use Illuminate\Cache\RedisStore;

class OpenSwooleRedisStore extends RedisStore
{

    /**
     * @var RedisPoolManager
     */
    protected $redis;

    public $config = [];

    /**
     * OpenSwooleRedisStore constructor.
     * @param RedisPoolManager $redis
     * @param string $prefix
     * @param string $connection
     * return void
     */
    public function __construct(RedisPoolManager $redis, $prefix = '', $connection = 'default')
    {
        $this->redis = $redis;
        $this->setPrefix($prefix);
        $this->setConnection($connection);
    }

    /**
     * Store an item in the cache if the key doesn't exist.
     *
     * @param  string $key
     * @param  mixed $value
     * @param  float|int $minutes
     * @return bool
     */
    public function add($key, $value, $minutes)
    {
        $lua = "return redis.call('exists',KEYS[1])<1 and redis.call('setex',KEYS[1],ARGV[2],ARGV[1])";

        return (bool)$this->connection()->eval(
            $lua, [$this->prefix . $key, $this->serialize($value), (int)max(1, $minutes * 60)], 1
        );
    }
}
