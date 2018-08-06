<?php

namespace WebonautePhpredisBundle\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use WebonautePhpredisBundle\Command\RedisFlushallCommand;
use WebonautePhpredisBundle\Tests\CommandTestCase;

/**
 * RedisFlushallCommandTest
 */
class RedisFlushAllCommandTest extends CommandTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->registerPhpredisClient();
    }

    public function testWithDefaultClientAndNoInteraction()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('webonaute_phpredis.default'));

        $this->phpredisClient->expects($this->once())
            ->method('flushAll')
            ->with($this->equalTo('flushAll'))
            ->will($this->returnValue(true));

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--no-interaction' => true));

        $this->assertRegExp('/All redis databases flushed/', $commandTester->getDisplay());
    }

    public function testClientOption()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('webonaute_phpredis.special'));

        $this->phpredisClient->expects($this->once())
            ->method('flushAll')
            ->with($this->equalTo('flushAll'))
            ->will($this->returnValue(true));

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'special', '--no-interaction' => true));

        $this->assertRegExp('/All redis databases flushed/', $commandTester->getDisplay());
    }

    public function testClientOptionWithNotExistingClient()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('webonaute_phpredis.notExisting'))
            ->will($this->throwException(new \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException('')));

        $command = $this->application->find('redis:flushall');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'notExisting', '--no-interaction' => true));

        $this->assertRegExp('/The client notExisting is not defined/', $commandTester->getDisplay());
    }

    protected function getCommand()
    {
        return new RedisFlushallCommand();
    }
}
