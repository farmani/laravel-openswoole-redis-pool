<?php

namespace Farmani\SwooleRedis;

use Illuminate\Redis\Connections\Connection;
use InvalidArgumentException;
use Illuminate\Contracts\Redis\Factory;

class RedisPoolManager implements Factory
{
    /**
     * The Redis server configurations.
     *
     * @var array
     */
    protected $config;

    /**
     * The Redis connections.
     *
     * @var mixed
     */
    protected $connections;

    protected $autoFill;

    /**
     * Create a new Redis manager instance.
     *
     * @param  array  $config
     * @param  bool   $autoFill
     */
    public function __construct(array $config, $autoFill = false)
    {
        $this->config = $config;
        $this->autoFill = $autoFill;
    }

    /**
     * Get a Redis pool connection by name.
     *
     * @param  string|null  $name
     *
     * @return SwooleRedisPoolConnection|Connection|mixed
     */
    public function connection($name = null)
    {
        $name = $name ?: 'default';
        if (isset($this->connections[$name])) {
            return $this->connections[$name];
        }

        return $this->connections[$name] = $this->resolve($name);
    }

    /**
     * Resolve the given connection by name.
     *
     * @param  string|null  $name
     *
     * @return \Illuminate\Redis\Connections\Connection
     *
     * @throws \InvalidArgumentException
     */
    public function resolve($name = null)
    {
        $name = $name ?: 'default';

        $options = $this->config['options'] ?? [];

        if (isset($this->config[$name])) {
            return $this->connect($this->config[$name], $options);
        }

        throw new InvalidArgumentException(
            "Redis connection [{$name}] not configured."
        );
    }

    /**
     * @param $config
     * @param $options
     *
     * @return \Illuminate\Redis\Connections\Connection
     */
    public function connect($config, $options = [])
    {
        return new SwooleRedisPoolConnection(new SwooleRedisPool($config, $this->autoFill));
    }

    /**
     * Return all of the created connections.
     *
     * @return array
     */
    public function connections()
    {
        return $this->connections;
    }
}
