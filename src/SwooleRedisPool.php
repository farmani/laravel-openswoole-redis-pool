<?php
/**
 * 功能说明
 * User: falco
 * Date: 4/23/21
 * Time: 11:26 AM
 */

namespace Farmani\SwooleRedis;

use Illuminate\Support\Facades\Log;
use OpenSwoole\Core\Coroutine\Client\RedisClientFactory;
use OpenSwoole\Core\Coroutine\Client\RedisConfig;
use OpenSwoole\Core\Coroutine\Pool\ClientPool;

class SwooleRedisPool
{
    protected ClientPool $pool;

    public array $config = [
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

    public function __construct($config, $options)
    {
        $this->config = array_merge($this->config, $config);
        $config = (new RedisConfig())
            ->withDbIndex($this->config['dbIndex'])
            ->withTimeout($this->config['timeout'])
            ->withReadTimeout($this->config['read_timeout'])
            ->withRetryInterval($this->config['retry_interval'])
            ->withHost($this->config['host'])
            ->withPort($this->config['port'])
            ->withAuth($this->config['auth']);


        $this->pool = new ClientPool(RedisClientFactory::class, $config, $this->config['poolMax']);
        $this->pool->fill();
    }

    /**
     * @return mixed|void
     */
    public function get()
    {
        $retry = -1;

        while (++$retry <= $this->config['retry_times']) {
            $redis = $this->pool->get();
            if ($redis->connected === true && $redis->errCode === 0) {
                return $redis;
            }

            $this->dumpError("redis-reconnect{$retry}，[errCode：{$redis->errCode}，errMsg：{$redis->errMsg}]");
            $redis->close();
            unset($redis);
        }

        $this->dumpError('Redis reconnection failed');
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
