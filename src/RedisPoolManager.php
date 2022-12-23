<?php

namespace Farmani\OpenSwooleRedis;

use Illuminate\Redis\Connections\Connection;
use InvalidArgumentException;
use Illuminate\Contracts\Redis\Factory;

class RedisPoolManager implements Factory
{
    /**
     * The Redis connections.
     *
     * @var mixed
     */
    protected mixed $connections;

    /**
     * Create a new Redis manager instance.
     *
     * @param  array  $config
     */
    public function __construct(protected array $config)
    {
    }

    /**
     * Get a Redis pool connection by name.
     *
     * @param  string|null  $name
     *
     * @return OpenSwooleRedisPoolConnection|Connection|mixed
     */
    public function connection($name = null): mixed
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
     * @param $name
     *
     * @return Connection|OpenSwooleRedisPoolConnection
     */
    public function resolve($name = null): Connection|OpenSwooleRedisPoolConnection
    {
        $name = $name ?: 'default';

        $options = $this->config['options'] ?? [];

        if (isset($this->config[$name])) {
            return $this->connect($this->config[$name], $options);
        }

        throw new InvalidArgumentException("Redis connection [{$name}] not configured.");
    }

    /**
     * @param $config
     * @param $options
     *
     * @return Connection|OpenSwooleRedisPoolConnection
     */
    public function connect($config, $options = []): Connection|OpenSwooleRedisPoolConnection
    {
        return new OpenSwooleRedisPoolConnection(new OpenSwooleRedisPool($config, true));
    }

    /**
     * Return all the created connections.
     *
     * @return array
     */
    public function connections(): array
    {
        return $this->connections;
    }
}
