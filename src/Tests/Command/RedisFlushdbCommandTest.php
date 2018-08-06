<?php

namespace WebonautePhpredisBundle\Tests\Command;

use Symfony\Component\Console\Tester\CommandTester;
use WebonautePhpredisBundle\Command\RedisFlushdbCommand;
use WebonautePhpredisBundle\Tests\CommandTestCase;

/**
 * RedisFlushdbCommandTest
 */
class RedisFlushdbCommandTest extends CommandTestCase
{

    public function setUp()
    {
        parent::setUp();

        $this->registerPhpRedisClient();
    }

    public function testDefaultClientAndNoInteraction()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('webonaute_phpredis.default'));

        $this->phpredisClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushdb'))
            ->will($this->returnValue(true));

        $command = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--no-interaction' => true));

        $this->assertRegExp('/redis database flushed/', $commandTester->getDisplay());
    }

    public function testClientOption()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('webonaute_phpredis.special'));

        $this->phpredisClient->expects($this->once())
            ->method('__call')
            ->with($this->equalTo('flushdb'))
            ->will($this->returnValue(true));

        $command = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'special', '--no-interaction' => true));

        $this->assertRegExp('/redis database flushed/', $commandTester->getDisplay());
    }

    public function testClientOptionWithNotExistingClient()
    {
        $this->container->expects($this->once())
            ->method('get')
            ->with($this->equalTo('webonaute_phpredis.notExisting'))
            ->will($this->throwException(new \Symfony\Component\DependencyInjection\Exception\ServiceNotFoundException('')));

        $this->phpredisClient->expects($this->never())
            ->method('__call');

        $command = $this->application->find('redis:flushdb');
        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName(), '--client' => 'notExisting', '--no-interaction' => true));

        $this->assertRegExp('/The client notExisting is not defined/', $commandTester->getDisplay());
    }

    protected function getCommand()
    {
        return new RedisFlushdbCommand();
    }
}
