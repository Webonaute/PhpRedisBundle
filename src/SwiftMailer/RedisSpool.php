<?php

namespace WebonautePhpredisBundle\SwiftMailer;

use WebonautePhpredisBundle\Client\RedisInterface;

/**
 * RedisSpool
 */
class RedisSpool extends \Swift_ConfigurableSpool
{
    /**
     * @var string
     */
    protected $key;

    /**
     * @var \Redis
     */
    protected $redis;

    /**
     * @param RedisInterface $redis
     */
    public function setRedis(RedisInterface $redis): void
    {
        $this->redis = $redis;
    }

    /**
     * @param string $key
     */
    public function setKey(string $key): void
    {
        $this->key = $key;
    }

    /**
     * {@inheritdoc}
     */
    public function start(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function stop(): void
    {
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function queueMessage(\Swift_Mime_SimpleMessage $message): bool
    {
        $this->redis->rPush($this->key, serialize($message));

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function flushQueue(\Swift_Transport $transport, &$failedRecipients = null): int
    {
        if (!$this->redis->lLen($this->key)) {
            return 0;
        }

        if (!$transport->isStarted()) {
            $transport->start();
        }

        $failedRecipients = (array)$failedRecipients;
        $count = 0;
        $time = time();

        while ($message = unserialize($this->redis->lpop($this->key))) {
            $count += $transport->send($message, $failedRecipients);

            if ($this->getMessageLimit() && $count >= $this->getMessageLimit()) {
                break;
            }

            if ($this->getTimeLimit() && (time() - $time) >= $this->getTimeLimit()) {
                break;
            }
        }

        return $count;
    }
}
