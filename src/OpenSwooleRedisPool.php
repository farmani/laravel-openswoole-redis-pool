<?php
/**
 * 功能说明
 * User: falco
 * Date: 4/23/21
 * Time: 11:26 AM
 */

namespace Farmani\OpenSwooleRedis;

use co;
use Farmani\OpenSwooleRedis\Exceptions\RedisConnectionNotFoundException;
use Illuminate\Support\Facades\Log;
use OpenSwoole\Core\Coroutine\Client\RedisClientFactory;
use OpenSwoole\Core\Coroutine\Client\RedisConfig;

class OpenSwooleRedisPool
{
    protected ClientPool $pool;

    public array $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'database' => '',
        'read_timeout' => 0.0,
        'timeout' => 0.0,
        'retry_interval' => 0,
        'reserved' => '',
        'pool' => [
            'min' => 16,
            'max' => 128,
            'idle_time' => -1,
            'idle_interval' => 1,
            'retry_interval' => 100,
            'retry_times' => 3,
            'heartbeat' => 3,
        ],
    ];

    public function __construct($config, $fill = false)
    {
        $this->config = array_merge($this->config, $config);
        $config = (new RedisConfig())
            ->withHost($this->config['host'])
            ->withPort($this->config['port'])
            ->withAuth($this->config['password'])
            ->withDbIndex($this->config['database'])
            ->withTimeout($this->config['timeout'])
            ->withReadTimeout($this->config['read_timeout'])
            ->withReserved($this->config['reserved'])
            ->withRetryInterval($this->config['retry_interval']);

        $this->pool = new ClientPool(
            RedisClientFactory::class,
            $config,
            $this->config['pool']['min'],
            $this->config['pool']['max'],
            $this->config['pool']['heartbeat']
        );
        $this->pool->setIdleInterval($this->config['pool']['idle_interval']);

        if ($fill) {
            $this->pool->fill();
        }
    }

    /**
     * @return mixed|void
     */
    public function get()
    {
        $retry = -1;

        while (++$retry <= $this->config['pool']['retry_times']) {
            $redis = $this->pool->get($this->config['pool']['idle_time']);
            if ($redis->connected === true && $redis->errCode === 0) {
                return $redis;
            }

            $this->dumpError("redis-reconnect{$retry}，[errCode：{$redis->errCode}，errMsg：{$redis->errMsg}]");
            $redis->close();
            unset($redis);
            co::usleep($this->config['pool']['retry_interval']);
        }

        throw new RedisConnectionNotFoundException('There is not any available Redis connection.');
    }

    public function put($redis): void
    {
        $this->pool->put($redis);
    }

    /**
     * @param $msg
     *
     * @return void
     */
    public function dumpError($msg): void
    {
        Log::error(date('Y-m-d H:i:s', time())."：{$msg}");
    }
}
