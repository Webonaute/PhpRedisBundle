<?php

namespace WebonautePhpredisBundle\DataCollector;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;
use WebonautePhpredisBundle\Logger\RedisLogger;

/**
 * RedisDataCollector
 */
class RedisDataCollector extends DataCollector
{
    private static $COMMANDS = 'commands';
    /**
     * @var RedisLogger
     */
    protected $logger;

    /**
     * Constructor.
     *
     * @param RedisLogger $logger
     */
    public function __construct(RedisLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Exception $exception = null): void
    {
        $this->data = [
            self::$COMMANDS => null !== $this->logger ? $this->logger->getCommands() : [],
        ];
    }

    /**
     * Returns an array of collected commands.
     *
     * @return array
     */
    public function getCommands(): array
    {
        return $this->data[self::$COMMANDS];
    }

    /**
     * Returns the number of collected commands.
     *
     * @return integer
     */
    public function getCommandCount(): int
    {
        return \count($this->data[self::$COMMANDS]);
    }

    /**
     * Returns the number of failed commands.
     *
     * @return integer
     */
    public function getErroredCommandsCount(): int
    {
        return \count(array_filter($this->data[self::$COMMANDS], function ($command) {
            return $command['error'] !== false;
        }));
    }

    /**
     * Returns the execution time of all collected commands in seconds.
     *
     * @return float
     */
    public function getTime(): float
    {
        $time = 0;
        foreach ($this->data[self::$COMMANDS] as $command) {
            $time += $command['executionMS'];
        }

        return $time;
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'redis';
    }

    public function reset(): void
    {
        $this->data = [];
    }
}
