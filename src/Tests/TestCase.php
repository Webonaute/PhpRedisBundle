<?php

namespace WebonautePhpredisBundle\Tests;

use PHPUnit\Framework\MockObject\MockBuilder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Kernel;
use WebonautePhpredisBundle\Client\RedisClient as PhpredisClient;
use WebonautePhpredisBundle\Client\RedisLoggedClient;

/**
 * Base Class for command tests
 *
 * @author Sebastian Göttschkes <sebastian.goettschkes@googlemail.com>
 */
abstract class TestCase extends BaseTestCase
{

    /**
     * @var Application
     */
    protected $application;

    /**
     * @var PhpredisClient
     */
    protected $phpredisClient;

    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var ContainerInterface
     */
    protected $testContainer;

    /**
     * SetUp called before each tests, setting up the environment (application, globally used mocks)
     */
    public function setUp()
    {
        $this->kernelBoot();
    }

    public function kernelBoot(): void
    {
        $this->container = $this->getMockBuilder(ContainerBuilder::class)->getMock();
        $this->testContainer = $this->container->has('test.service_container') ? $this->container->get('test.service_container') : $this->container;

        /** @var Kernel|MockBuilder $kernel */
        $kernel = $this->getMockBuilder(Kernel::class)
            ->disableOriginalConstructor()
            ->getMock();
        $kernel->expects($this->once())
            ->method('getBundles')
            ->will($this->returnValue(array()));
        $kernel->expects($this->atLeastOnce())
            ->method('getContainer')
            ->will($this->returnValue($this->container));
        $this->application = new Application($kernel);

        $this->registerPhpredisClient();
    }

    protected function registerPhpredisClient(): void
    {
        $this->phpredisClient = $this->getMockBuilder(RedisLoggedClient::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->container->expects($this->once())
            ->method('get')
            ->will($this->returnValue($this->phpredisClient));
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getTestContainer(): ContainerInterface
    {
        return $this->container;
    }
}
