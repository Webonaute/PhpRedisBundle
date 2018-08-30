<?php

namespace WebonautePhpredisBundle\Doctrine\Common\Cache;

use Doctrine\Common\Cache\Cache;
use Doctrine\Common\Cache\CacheProvider;
use Redis;
use WebonautePhpredisBundle\Client\RedisCluster;
use function array_combine;
use function defined;
use function extension_loaded;
use function is_bool;

/**
 * Redis cache provider.
 *
 * @link   www.doctrine-project.org
 */
class RedisClusterCache extends CacheProvider
{
    /** @var RedisCluster|null */
    private $redis;

    /**
     * Gets the redis instance used by the cache.
     *
     * @return RedisCluster|null
     */
    public function getRedis(): ?RedisCluster
    {
        return $this->redis;
    }

    /**
     * Sets the redis instance to use.
     *
     * @param RedisCluster $redis
     *
     * @return void
     */
    public function setRedis(RedisCluster $redis): void
    {
        $redis->setOption(Redis::OPT_SERIALIZER, $this->getSerializerValue());
        $this->redis = $redis;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetch($id)
    {
        return $this->redis->get($id);
    }

    /**
     * {@inheritdoc}
     */
    protected function doFetchMultiple(array $keys): array
    {
        $fetchedItems = array_combine($keys, $this->redis->mget($keys));

        // Redis mget returns false for keys that do not exist. So we need to filter those out unless it's the real data.
        $foundItems = [];

        foreach ($fetchedItems as $key => $value) {
            if ($value === false && !$this->redis->exists($key)) {
                continue;
            }

            $foundItems[$key] = $value;
        }

        return $foundItems;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSaveMultiple(array $keysAndValues, $lifetime = 0): bool
    {
        if ($lifetime) {
            $success = true;

            // Keys have lifetime, use SETEX for each of them
            foreach ($keysAndValues as $key => $value) {
                if ($this->redis->setex($key, $lifetime, $value)) {
                    continue;
                }

                $success = false;
            }

            return $success;
        }

        // No lifetime, use MSET
        return (bool)$this->redis->mset($keysAndValues);
    }

    /**
     * {@inheritdoc}
     */
    protected function doContains($id): bool
    {
        $exists = $this->redis->exists($id);

        if (is_bool($exists)) {
            return $exists;
        }

        return $exists > 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doSave($id, $data, $lifeTime = 0): bool
    {
        if ($lifeTime > 0) {
            return $this->redis->setex($id, $lifeTime, $data);
        }

        return $this->redis->set($id, $data);
    }

    /**
     * {@inheritdoc}
     */
    protected function doDelete($id): bool
    {
        return $this->redis->del($id) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doDeleteMultiple(array $keys): bool
    {
        return $this->redis->del($keys) >= 0;
    }

    /**
     * {@inheritdoc}
     */
    protected function doFlush()
    {
        return $this->redis->flushDB();
    }

    /**
     * {@inheritdoc}
     */
    protected function doGetStats(): ?array
    {
        $info = $this->redis->info();
        return [
            Cache::STATS_HITS => $info['keyspace_hits'],
            Cache::STATS_MISSES => $info['keyspace_misses'],
            Cache::STATS_UPTIME => $info['uptime_in_seconds'],
            Cache::STATS_MEMORY_USAGE => $info['used_memory'],
            Cache::STATS_MEMORY_AVAILABLE => false,
        ];
    }

    /**
     * Returns the serializer constant to use. If Redis is compiled with
     * igbinary support, that is used. Otherwise the default PHP serializer is
     * used.
     *
     * @return int One of the RedisCluster::SERIALIZER_* constants
     */
    protected function getSerializerValue(): int
    {
        if (defined('RedisCluster::SERIALIZER_IGBINARY') && extension_loaded('igbinary')) {
            return RedisCluster::SERIALIZER_IGBINARY;
        }

        return RedisCluster::SERIALIZER_PHP;
    }
}
