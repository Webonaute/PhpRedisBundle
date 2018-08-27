<?php

namespace WebonautePhpredisBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException;
use WebonautePhpredisBundle\Pool\Pool;

/**
 * Base command for redis interaction through the command line
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
abstract class RedisBaseCommand extends ContainerAwareCommand
{

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    protected $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    protected $output;

    /**
     * @var \WebonautePhpredisBundle\Client\RedisClient
     */
    protected $redisClient;

    /**
     * @var Pool
     */
    protected $pool;

    /**
     * RedisBaseCommand constructor.
     *
     * @param Pool $pool
     * @param null|string $name
     */
    public function __construct(Pool $pool, ?string $name = null)
    {
        $this->pool = $pool;
        parent::__construct($name);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure(): void
    {
        $this->addOption(
            'client',
            null,
            InputOption::VALUE_REQUIRED, 'The name of the phpredis client to interact with',
            'default'
        );
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->input = $input;
        $this->output = $output;

        $client = $this->input->getOption('client');
        try {
            $this->redisClient = $this->pool->get($client);
        } catch (ServiceNotFoundException $e) {
            $this->output->writeln('<error>The client ' . $client . ' is not defined</error>');
            return;
        }

        $this->executeRedisCommand();
    }

    /**
     * Method which gets called by execute(). Used for code unique to the command
     */
    abstract protected function executeRedisCommand();

    /**
     * Checks if either the no-interaction option was chosen or asks the user to proceed
     *
     * @return boolean true if either no-interaction was chosen or the user wants to proceed
     */
    protected function proceedingAllowed(): bool
    {
        if ($this->input->getOption('no-interaction')) {
            return true;
        }

        return $this->getHelper('question')->ask($this->input, $this->output,
            new ConfirmationQuestion('<question>Are you sure you wish to flush the whole database? (y/n)</question>', false));
    }
}
