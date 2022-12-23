<?php
/**
 * 功能说明
 * User: falco
 * Date: 4/23/21
 * Time: 11:26 AM
 */

namespace Farmani\OpenSwooleRedis;

use Farmani\OpenSwooleRedis\Exceptions\RedisConnectionNotFoundException;
use Illuminate\Support\Facades\Log;
use OpenSwoole\Core\Coroutine\Client\RedisClientFactory;
use OpenSwoole\Core\Coroutine\Client\RedisConfig;

class OpenSwooleRedisPool
{
    protected ClientPool $pool;

    public array $config = [
        'pool_min' => 16,
        'pool_max' => 128,
        'pool_idle_time' => 15,
        'pool_idle_interval' => 3,

        'read_timeout' => 0.0,
        'timeout' => 0.0,
        'retry_interval' => 100,
        'retry_times' => 3,
    ];

    public function __construct($config, $fill = false)
    {
        $this->config = array_merge($this->config, $config);
        $config = (new RedisConfig())
            ->withHost(config('database.redis.'.config('cache.redis_pool.connection').'.host'))
            ->withPort(config('database.redis.'.config('cache.redis_pool.connection').'.port'))
            ->withAuth(config('database.redis.'.config('cache.redis_pool.connection').'.password'))
            ->withDbIndex(config('database.redis.'.config('cache.redis_pool.connection').'.database'))
            ->withTimeout($this->config['timeout'])
            ->withReadTimeout($this->config['read_timeout'])
            ->withRetryInterval($this->config['retry_interval']);

        $this->pool = new ClientPool(RedisClientFactory::class, $config, $this->config['pool_min'], $this->config['pool_max']);
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

        while (++$retry <= $this->config['retry_times']) {
            $redis = $this->pool->get();
            if ($redis->connected === true && $redis->errCode === 0) {
                return $redis;
            }

            $this->dumpError("redis-reconnect{$retry}，[errCode：{$redis->errCode}，errMsg：{$redis->errMsg}]");
            $redis->close();
            unset($redis);
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
