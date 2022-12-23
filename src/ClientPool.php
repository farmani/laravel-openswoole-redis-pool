<?php

namespace Farmani\OpenSwooleRedis;

use OpenSwoole\Coroutine as Co;
use OpenSwoole\Core\Coroutine\Client\RedisConfig;
use OpenSwoole\Coroutine;
use OpenSwoole\Coroutine\Channel;

/**
 *
 */
class ClientPool
{
    /**
     *
     */
    public const DEFAULT_MIN_SIZE = 16;
    /**
     *
     */
    public const DEFAULT_MAX_SIZE = 128;

    /**
     * @var Channel
     */
    private $pool;

    /**
     * @var int
     */
    public $num;

    /**
     * @var
     */
    private $active;

    /**
     * @var
     */
    private $factory;

    /**
     * @var
     */
    private $idleInterval;

    /**
     * @param               $factory
     * @param  RedisConfig  $config
     * @param  int          $min
     * @param  int          $max
     * @param  int          $heartbeat
     */
    public function __construct(
        $factory,
        private RedisConfig $config,
        private readonly int $min = self::DEFAULT_MIN_SIZE,
        private readonly int $max = self::DEFAULT_MAX_SIZE,
        private readonly int $heartbeat = 0
    ) {
        $this->pool = new Channel($max);
        $this->num = 0;
        $this->factory = $factory;
        $this->fill($this->min);
        if ($this->heartbeat) {
            $this->heartbeat();
        }
    }

    /**
     * @param ?int  $size
     *
     * @return void
     */
    public function fill(int $size = null): void
    {
        $size = $size ?? $this->max;
        while ($size > $this->num) {
            $this->make();
        }
    }

    /**
     * @param  float  $timeout
     *
     * @return mixed
     */
    public function get(float $timeout = -1)
    {
        if ($this->pool->isEmpty() && $this->num < $this->max) {
            $this->make();
        }

        $this->active++;

        return $this->pool->pop($timeout);
    }

    /**
     * @param $connection
     * @param $isNew
     *
     * @return void
     */
    public function put($connection, $isNew = false): void
    {
        if ($this->pool === null) {
            return;
        }
        if ($connection !== null) {
            $this->pool->push($connection);

            if (!$isNew) {
                $this->active--;
            }
        } else {
            $this->num -= 1;
            $this->make();
        }
    }

    /**
     * @return void
     */
    public function close(): void
    {
        if (!$this->pool) {
            return;
        }

        while (1) {
            if ($this->active > 0) {
                Co::usleep($this->idleInterval);
                continue;
            }
            if (!$this->pool->isEmpty()) {
                $client = $this->pool->pop();
                $client->close();
            } else {
                break;
            }
        }

        $this->pool->close();
        $this->pool = null;
        $this->num = 0;
    }

    /**
     * @return void
     */
    protected function make(): void
    {
        $this->num++;
        $client = $this->factory::make($this->config);
        $this->put($client, true);
    }

    /**
     * @return void
     */
    protected function heartbeat(): void
    {
        Coroutine::create(function () {
            while ($this->pool) {
                Co::usleep($this->heartbeat);
                $client = $this->get();
                $client->heartbeat();
                $this->put($client);
            }
        });
    }

    /**
     * @param  int  $second
     *
     * @return void
     */
    public function setIdleInterval(int $second): void
    {
        $this->idleInterval = $second;
    }
}
