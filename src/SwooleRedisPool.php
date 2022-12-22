<?php
/**
 * 功能说明
 * User: falco
 * Date: 4/23/21
 * Time: 11:26 AM
 */

namespace Farmani\SwooleRedis;

use OpenSwoole\Coroutine\Channel;
use Illuminate\Support\Facades\Log;
use OpenSwoole\Core\Coroutine\Client\RedisClientFactory;
use OpenSwoole\Core\Coroutine\Client\RedisConfig;
use OpenSwoole\Core\Coroutine\Pool\ClientPool;

class SwooleRedisPool
{
    protected ClientPool $pool;

    protected $pushTime = 0;

    public array $config = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'auth' => '',
        'dbIndex' => 0,
        'poolSize' => 64,
        'read_timeout' => 0.0,
        'timeout' => 0.0,
        'retry_interval' => 0,
        'retry_times' => 3,
    ];

    public function __construct($config, $autoFill = false)
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
        $re_i = -1;

        back:
        $re_i++;

        $redis = $this->pool->get();

        if ($redis->connected === true && $redis->errCode === 0) {
            return $redis;
        } else {
            if ($re_i <= $this->config['retry_times']) {
                $this->dumpError("redis-reconnect{$re_i}，[errCode：{$redis->errCode}，errMsg：{$redis->errMsg}]");

                $redis->close();
                unset($redis);
                goto back;
            }
            $this->dumpError('Redis reconnection failed');
        }
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
