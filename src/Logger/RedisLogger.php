<?php

namespace WebonautePhpredisBundle\Logger;

use Psr\Log\LoggerInterface;

/**
 * RedisLogger
 */
class RedisLogger
{

    protected $commands = [];

    protected $logger;

    protected $nbCommands = 0;

    /**
     * Constructor.
     *
     * @param LoggerInterface $logger A LoggerInterface instance
     */
    public function __construct(LoggerInterface $logger = null)
    {
        $this->logger = $logger;
    }

    /**
     * Logs a command
     *
     * @param string $command Redis command
     * @param float $duration Duration in milliseconds
     * @param string $connection Connection alias
     * @param bool|string $error Error message or false if command was successful
     */
    public function logCommand($command, $duration, $connection, $error = false)
    {
        ++$this->nbCommands;

        if (null !== $this->logger) {
            $this->commands[] = ['cmd' => $command, 'executionMS' => $duration, 'conn' => $connection, 'error' => $error];
            if ($error) {
                $message = 'Command "' . $command . '" failed (' . $error . ')';
                $this->logger->error($message);
            } else {
                $this->logger->debug('Executing command "' . $command . '"');
            }
        }
    }

    /**
     * Returns the number of logged commands.
     *
     * @return integer
     */
    public function getNbCommands()
    {
        return $this->nbCommands;
    }

    /**
     * Returns an array of the logged commands.
     *
     * @return array
     */
    public function getCommands()
    {
        return $this->commands;
    }
}
