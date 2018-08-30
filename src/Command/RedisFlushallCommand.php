<?php

namespace WebonautePhpredisBundle\Command;


/**
 * Symfony command to execute redis flushall
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
class RedisFlushallCommand extends RedisBaseCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('redis:flushall')
            ->setDescription('Flushes the redis database using the redis flushall command');
    }

    /**
     * {@inheritDoc}
     */
    protected function executeRedisCommand(): void
    {
        if ($this->proceedingAllowed()) {
            $this->flushAll();
        } else {
            $this->output->writeln('<error>Flushing cancelled</error>');
        }
    }

    /**
     * Flushing all redis databases
     */
    private function flushAll(): void
    {
        if ($this->redisClient instanceof \Redis) {
            $this->redisClient->flushAll();
        } elseif ($this->redisClient instanceof \RedisCluster) {
            foreach ($this->redisClient->_masters() as $node) {
                $this->redisClient->flushAll($node);
            }
        }

        $this->output->writeln('<info>All redis databases flushed</info>');
    }

}
