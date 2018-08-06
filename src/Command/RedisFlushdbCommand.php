<?php

namespace WebonautePhpredisBundle\Command;

/**
 * Symfony command to execute redis flushdb
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
class RedisFlushdbCommand extends RedisBaseCommand
{

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        parent::configure();

        $this->setName('redis:flushdb')
            ->setDescription('Flushes the redis database using the redis flushdb command');
    }

    /**
     * {@inheritDoc}
     */
    protected function executeRedisCommand(): void
    {
        if ($this->proceedingAllowed()) {
            $this->flushDbForClient();
        } else {
            $this->output->writeln('<error>Flushing cancelled</error>');
        }
    }

    /**
     * Getting the client from cmd option and flush's the db
     */
    private function flushDbForClient(): void
    {
        $this->redisClient->flushDB();

        $this->output->writeln('<info>redis database flushed</info>');
    }

}

