<?php

namespace WebonautePhpredisBundle\Session\Storage\Handler;

use WebonautePhpredisBundle\Client\RedisInterface;

/**
 * Redis based session storage with session locking support.
 *
 *
 */
class RedisSessionHandler implements \SessionHandlerInterface
{
    /**
     * @var int Default PHP max execution time in seconds
     */
    public const DEFAULT_MAX_EXECUTION_TIME = 30;
    /**
     * @var \Redis|\RedisCluster|RedisInterface
     */
    protected $redis;
    /**
     * @var int
     */
    protected $ttl;
    /**
     * @var string
     */
    protected $prefix;
    /**
     * @var bool Indicates an sessions should be locked
     */
    protected $locking;

    /**
     * @var bool Indicates an active session lock
     */
    protected $locked;

    /**
     * @var string Session lock key
     */
    private $lockKey;

    /**
     * @var string Session lock token
     */
    private $token;

    /**
     * @var int Microseconds to wait between acquire lock tries
     */
    private $spinLockWait;

    /**
     * @var int Maximum amount of seconds to wait for the lock
     */
    private $lockMaxWait;

    /**
     * Redis session storage constructor.
     *
     * @param RedisInterface $redis Redis database connection
     * @param array $options Session options
     * @param string $prefix Prefix to use when writing session data
     * @param bool $locking
     * @param int $spinLockWait
     */
    public function __construct(RedisInterface $redis, array $options = array(), $prefix = 'session', $locking = true, $spinLockWait = 150000)
    {
        $this->redis = $redis;
        $this->ttl = isset($options['gc_maxlifetime']) ? (int)$options['gc_maxlifetime'] : 0;
        if (isset($options['cookie_lifetime']) && $options['cookie_lifetime'] > $this->ttl) {
            $this->ttl = (int)$options['cookie_lifetime'];
        }
        $this->prefix = $prefix;

        $this->locking = $locking;
        $this->locked = false;
        $this->lockKey = null;
        $this->spinLockWait = $spinLockWait;
        $this->lockMaxWait = ini_get('max_execution_time');
        if (!$this->lockMaxWait) {
            $this->lockMaxWait = self::DEFAULT_MAX_EXECUTION_TIME;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function open($savePath, $sessionName): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function read($sessionId)
    {
        if ($this->locking && !$this->locked && !$this->lockSession($sessionId)) {
            return false;
        }

        return $this->redis->get($this->getRedisKey($sessionId)) ?: '';
    }

    /**
     * Lock the session data.
     * @param $sessionId
     * @return bool
     */
    protected function lockSession($sessionId): bool
    {
        $attempts = (1000000 / $this->spinLockWait) * $this->lockMaxWait;

        $this->token = uniqid('', true);

        $this->lockKey = $sessionId . '.lock';
        for ($i = 0; $i < $attempts; ++$i) {

            // We try to aquire the lock
            $setFunction = function (RedisInterface $redis, $key, $token, $ttl) {
                return $redis->set(
                    $key,
                    $token,
                    array('NX', 'PX' => $ttl)
                );
            };
            $success = $setFunction($this->redis, $this->getRedisKey($this->lockKey), $this->token, $this->lockMaxWait * 1000 + 1);
            if ($success) {
                $this->locked = true;

                return true;
            }

            usleep($this->spinLockWait);
        }

        return false;
    }

    /**
     * Prepends the given key with a user-defined prefix (if any).
     *
     * @param string $key key
     *
     * @return string prefixed key
     */
    protected function getRedisKey($key): string
    {
        if (empty($this->prefix)) {
            return $key;
        }

        return $this->prefix . $key;
    }

    /**
     * {@inheritdoc}
     */
    public function write($sessionId, $data): bool
    {
        if (0 < $this->ttl) {
            $this->redis->setex($this->getRedisKey($sessionId), $this->ttl, $data);
        } else {
            $this->redis->set($this->getRedisKey($sessionId), $data);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function destroy($sessionId): bool
    {
        $this->redis->del($this->getRedisKey($sessionId));
        $this->close();

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function close(): bool
    {
        if ($this->locking && $this->locked) {
            $this->unlockSession();
        }

        return true;
    }

    /**
     * Unlock the session data.
     */
    private function unlockSession(): void
    {
        // If we have the right token, then delete the lock
        $script = <<<LUA
if redis.call("GET", KEYS[1]) == ARGV[1] then
    return redis.call("DEL", KEYS[1])
else
    return 0
end
LUA;


        $this->redis->eval($script, [$this->getRedisKey($this->lockKey), $this->token], 1);

        $this->locked = false;
        $this->token = null;
    }

    /**
     * {@inheritdoc}
     */
    public function gc($lifetime): bool
    {
        return true;
    }

    /**
     * Change the default TTL.
     *
     * @param int $ttl
     */
    public function setTtl($ttl): void
    {
        $this->ttl = $ttl;
    }

    /**
     * Destructor.
     */
    public function __destruct()
    {
        $this->close();
    }
}
