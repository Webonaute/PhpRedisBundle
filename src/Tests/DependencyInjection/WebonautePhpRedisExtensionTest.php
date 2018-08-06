<?php

namespace WebonautePhpredisBundle\Tests\DependencyInjection;

use Doctrine\Common\Cache\RedisCache;
use Monolog\Formatter\LogstashFormatter;
use Monolog\Handler\RedisHandler;
use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\Yaml\Parser;
use WebonautePhpredisBundle\DataCollector\RedisDataCollector;
use WebonautePhpredisBundle\DependencyInjection\Configuration\Configuration;
use WebonautePhpredisBundle\DependencyInjection\Configuration\RedisDsn;
use WebonautePhpredisBundle\DependencyInjection\WebonautePhpredisExtension;
use WebonautePhpredisBundle\Logger\RedisLogger;
use WebonautePhpredisBundle\Session\Storage\Handler\RedisSessionHandler;
use WebonautePhpredisBundle\SwiftMailer\RedisSpool;
use WebonautePhpredisBundle\Tests\TestCase;

/**
 * WebonautePhpRedisExtensionTest
 */
class WebonautePhpRedisExtensionTest extends TestCase
{
    /**
     * @static
     *
     * @return array
     */
    public static function parameterValues(): array
    {
        return array(
            array('webonaute_phpredis.logger.class', RedisLogger::class),
            array('webonaute_phpredis.data_collector.class', RedisDataCollector::class),
            array('webonaute_phpredis.doctrine_cache_phpredis.class', RedisCache::class),
            array('webonaute_phpredis.monolog_handler.class', RedisHandler::class),
            array('webonaute_phpredis.swiftmailer_spool.class', RedisSpool::class),
        );
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testEmptyConfigLoad(): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = array();
        $extension->load(array($config), $this->getContainer());
    }

    /**
     * @param string $name Name
     * @param string $expected Expected value
     *
     * @dataProvider parameterValues
     * @throws \Exception
     */
    public function testDefaultParameterConfigLoad($name, $expected): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertEquals($expected, $container->getParameter($name));
    }

    private function parseYaml($yaml)
    {
        $parser = new Parser();

        return $parser->parse($yaml);
    }

    private function getMinimalYamlConfig(): string
    {
        return <<<'EOF'
clients:
    default:
        type: single
        alias: default
        dsn: redis://localhost
EOF;
    }

    /**
     * @param string $name Name
     * @param string $expected Expected value
     *
     * @dataProvider parameterValues
     * @throws \Exception
     */
    public function testDefaultClientTaggedServicesConfigLoad($name, $expected): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getMinimalYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $this->assertInternalType('array', $container->findTaggedServiceIds('webonaute_phpredis.client'));
        $this->assertCount(1, $container->findTaggedServiceIds('webonaute_phpredis.client'), 'Minimal Yaml should have tagged 1 client');
    }

    /**
     * Test loading of minimal config
     */
    public function testMinimalConfigLoad(): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getMinimalYamlConfig());
        $container = $this->getContainer();
        $extension->load(array($config), $container);
        $this->assertTrue($container->hasDefinition('webonaute_phpredis.logger'));
        $this->assertTrue($container->hasDefinition('webonaute_phpredis.data_collector'));

        $this->assertTrue($container->hasDefinition('webonaute_phpredis.connection.default_parameters.default'));
        $this->assertTrue($container->hasDefinition('webonaute_phpredis.client.default_profile'));
        $this->assertTrue($container->hasDefinition('webonaute_phpredis.client.default_options'));
        $this->assertTrue($container->hasDefinition('webonaute_phpredis.default'));
        $this->assertTrue($container->hasAlias('webonaute_phpredis.default_client'));
        $this->assertInternalType('array', $container->findTaggedServiceIds('webonaute_phpredis.client'));
        $this->assertEquals(array('webonaute_phpredis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('webonaute_phpredis.client'));
    }

    /**
     * Test loading of full config
     */
    public function testFullConfigLoad(): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getFullYamlConfig());
        $container = $this->getContainer();
        $extension->load(array($config), $container);

        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.logger'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.data_collector'));

        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.connection.default_parameters.default'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.client.default_profile'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.client.default_options'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.default'));
        $this->assertTrue($this->getTestContainer()->hasAlias('webonaute_phpredis.default_client'));

        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.connection.cache_parameters.cache'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.client.cache_profile'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.client.cache_options'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.cache'));
        $this->assertTrue($this->getTestContainer()->hasAlias('webonaute_phpredis.cache_client'));

        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.connection.monolog_parameters.monolog'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.client.monolog_profile'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.client.monolog_options'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.monolog'));
        $this->assertTrue($this->getTestContainer()->hasAlias('webonaute_phpredis.monolog_client'));

        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.connection.cluster1_parameters.cluster'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.connection.cluster2_parameters.cluster'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.connection.cluster3_parameters.cluster'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.client.cluster_profile'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.client.cluster_options'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.cluster'));
        $this->assertTrue($this->getTestContainer()->hasAlias('webonaute_phpredis.cluster_client'));

        $this->assertTrue($this->getTestContainer()->hasDefinition(RedisSessionHandler::class));

        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine.orm.default_metadata_cache'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine.orm.default_result_cache'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine.orm.default_query_cache'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine.orm.default_second_level_cache.region_cache_driver'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine.orm.read_result_cache'));

        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine_mongodb.odm.default_metadata_cache'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine_mongodb.odm.default_result_cache'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine_mongodb.odm.default_query_cache'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine_mongodb.odm.slave1_result_cache'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('doctrine_mongodb.odm.slave2_result_cache'));

        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.monolog'));
        $this->assertTrue($this->getTestContainer()->hasAlias('webonaute_phpredis.monolog_client'));
        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.monolog.handler'));

        $this->assertTrue($this->getTestContainer()->hasDefinition('webonaute_phpredis.swiftmailer.spool'));
        $this->assertTrue($this->getTestContainer()->hasAlias('swiftmailer.spool.redis'));

        $this->assertInternalType('array', $this->getTestContainer()->findTaggedServiceIds('webonaute_phpredis.client'));
        $this->assertGreaterThanOrEqual(4, count($this->getTestContainer()->findTaggedServiceIds('webonaute_phpredis.client')), 'expected at least 4 tagged clients');

        $tags = $this->getTestContainer()->findTaggedServiceIds('webonaute_phpredis.client');
        $this->assertArrayHasKey('webonaute_phpredis.default', $tags);
        $this->assertArrayHasKey('webonaute_phpredis.cache', $tags);
        $this->assertArrayHasKey('webonaute_phpredis.monolog', $tags);
        $this->assertArrayHasKey('webonaute_phpredis.cluster', $tags);
        $this->assertArraySubset(array('webonaute_phpredis.cache' => array(array('alias' => 'cache'))), $tags);
        $this->assertArraySubset(array('webonaute_phpredis.cluster' => array(array('alias' => 'cluster'))), $tags);
    }

    private function getFullYamlConfig(): string
    {
        return <<<'EOF'
clients:
    default:
        type: single
        alias: default
        dsn: redis://localhost
        logging: true
        options:
            profile: 2.0
            prefix: webonaute:
    cache:
        type: single
        alias: cache
        dsn: redis://localhost/1
        logging: true
    monolog:
        type: single
        alias: monolog
        dsn: redis://localhost/1
        logging: false
    cluster:
        type: cluster
        alias: cluster
        dsn:
            - redis://127.0.0.1/1
            - redis://127.0.0.2/2
            - redis://pw@/var/run/redis/redis-1.sock/10
            - redis://pw@127.0.0.1:63790/10
        options:
            profile: 2.4
            connection_timeout: 10
            connection_persistent: true
            read_write_timeout: 30
            iterable_multibulk: false
            throw_errors: true
            replication: false
            parameters:
                database: 1
                password: pass
session:
    client: default
    prefix: foo
    ttl: 1440
doctrine:
    metadata_cache:
        client: cache
        entity_manager: default
        document_manager: default
    result_cache:
        client: cache
        entity_manager: [default, read]
        document_manager: [default, slave1, slave2]
        namespace: "dcrc:"
    query_cache:
        client: cache
        entity_manager: default
        document_manager: default
    second_level_cache:
        client: cache
        entity_manager: default
        document_manager: default
monolog:
    client: monolog
    key: monolog
swiftmailer:
    client: default
    key: swiftmailer
profiler_storage:
    client: default
    ttl: 3600
EOF;
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     */
    public function testInvalidMonologConfigLoad(): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getInvalidMonologYamlConfig());
        $extension->load(array($config), $this->getContainer());
    }

    private function getInvalidMonologYamlConfig(): string
    {
        return <<<'EOF'
clients:
    monolog:
        type: single
        alias: monolog
        dsn: redis://localhost
        logging: true
monolog:
    client: monolog
    key: monolog
EOF;
    }

    /**
     * Test the monolog formatter option
     */
    public function testMonologFormatterOption(): void
    {
        $this->kernelBoot();
        $container = $this->getContainer();
        //Create a fake formatter definition
        $container->setDefinition('my_monolog_formatter', new Definition(LogstashFormatter::class, array('symfony')));
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getMonologFormatterOptionYamlConfig());
        $extension->load(array($config), $container);

        $loggerDefinition = $container->getDefinition('webonaute_phpredis.monolog.handler');
        $calls = $loggerDefinition->getMethodCalls();
        $this->assertTrue($loggerDefinition->hasMethodCall('setFormatter'));
        $calls = $loggerDefinition->getMethodCalls();
        foreach ($calls as $call) {
            if ($call[0] === 'setFormatter') {
                $this->assertEquals('my_monolog_formatter', (string)$call[1][0]);
                break;
            }
        }
    }

    private function getMonologFormatterOptionYamlConfig(): string
    {
        return <<<'EOF'
clients:
    monolog:
        type: single
        alias: monolog
        dsn: redis://localhost
        logging: false
monolog:
    client: monolog
    key: monolog
    formatter: my_monolog_formatter
EOF;
    }

    /**
     * Test valid parsing of the client profile option
     */
    public function testClientProfileOption(): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getFullYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $profileDefinition = $container->getDefinition('webonaute_phpredis.client.default_profile');
        $options = $container->getDefinition('webonaute_phpredis.client.default_options')->getArgument(0);

        $this->assertSame((float)2, $config['clients']['default']['options']['profile'], 'Profile version 2.0 was parsed as float');

        $this->assertSame('webonaute:', $options['prefix'], 'Prefix option was allowed');
    }

    /**
     * Test multiple clients both containing "master" dsn aliases
     */
    public function testMultipleClientMaster(): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getMultipleReplicationYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $defaultParameters = $container->getDefinition('webonaute_phpredis.default')->getArgument(0);
        $this->assertEquals('webonaute_phpredis.connection.master_parameters.default', (string)$defaultParameters[0]);
        $defaultMasterParameters = $container->getDefinition((string)$defaultParameters[0])->getArgument(0);
        $this->assertEquals('defaultprefix', $defaultMasterParameters['prefix']);

        $secondParameters = $container->getDefinition('webonaute_phpredis.second')->getArgument(0);
        $this->assertEquals('webonaute_phpredis.connection.master_parameters.second', (string)$secondParameters[0]);
        $secondMasterParameters = $container->getDefinition((string)$secondParameters[0])->getArgument(0);
        $this->assertEquals('secondprefix', $secondMasterParameters['prefix']);
    }

    private function getMultipleReplicationYamlConfig(): string
    {
        return <<<'EOF'
clients:
    default:
        type: cluster
        alias: default
        dsn:
            - redis://defaulthost?alias=master
            - redis://defaultslave
        options:
            replication: true
            prefix: defaultprefix
    second:
        type: cluster
        alias: second
        dsn:
            - redis://secondmaster?alias=master
            - redis://secondslave
        options:
            replication: true
            prefix: secondprefix
EOF;
    }

    /**
     * Test valid XML config
     *
     */
    public function testValidXmlConfig(): void
    {
        $this->kernelBoot();
        $container = $this->getContainer();
        $container->registerExtension(new WebonautePhpredisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config'));
        $loader->load('valid.xml');

        $this->assertTrue(TRUE);
    }

    /**
     * @expectedException \InvalidArgumentException
     */
    public function testInvalidXmlConfig(): void
    {
        $this->kernelBoot();
        $container = $this->getContainer();
        $container->registerExtension(new WebonautePhpredisExtension());
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__ . '/Fixtures/config'));
        $loader->load('invalid.xml');
    }

    /**
     * Test config merging
     */
    public function testConfigurationMerging(): void
    {
        $this->kernelBoot();
        $configuration = new Configuration(true);
        $configs = array($this->parseYaml($this->getMergeConfig1()), $this->parseYaml($this->getMergeConfig2()));
        $processor = new Processor();
        $config = $processor->processConfiguration($configuration, $configs);
        $this->assertCount(1, $config['clients']['default']['dsns']);
        $this->assertEquals(new RedisDsn('redis://test'), current($config['clients']['default']['dsns']));
    }

    private function getMergeConfig1(): string
    {
        return <<<'EOF'
clients:
    default:
        type: single
        alias: default
        dsn: [ redis://default/1, redis://default/2 ]
        logging: true
EOF;
    }

    private function getMergeConfig2(): string
    {
        return <<<'EOF'
clients:
    default:
        dsn: redis://test
EOF;
    }

    /**
     * Test valid config of the replication option
     */
    public function testClientReplicationOption(): void
    {
        $this->markTestSkipped('not yet implemented');
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getReplicationYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $options = $container->getDefinition('webonaute_phpredis.client.default_options')->getArgument(0);
        $this->assertTrue($options['replication']);
        $parameters = $container->getDefinition('webonaute_phpredis.default')->getArgument(0);
        $this->assertEquals('webonaute_phpredis.connection.master_parameters.default', (string)$parameters[0]);
        $masterParameters = $container->getDefinition((string)$parameters[0])->getArgument(0);
        $this->assertTrue($masterParameters['replication']);

        $this->assertInternalType('array', $container->findTaggedServiceIds('webonaute_phpredis.client'));
        $this->assertEquals(array('webonaute_phpredis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('webonaute_phpredis.client'));
    }

    private function getReplicationYamlConfig(): string
    {
        return <<<'EOF'
clients:
    default:
        type: single
        alias: default
        dsn:
            - redis://localhost?alias=master
            - redis://otherhost
        options:
            replication: true
EOF;
    }

    /**
     * Test valid config of the sentinel replication option
     */
    public function testSentinelOption(): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getSentinelYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $options = $container->getDefinition('webonaute_phpredis.client.default_options')->getArgument(0);
        $this->assertEquals('sentinel', $options['replication']);
        $this->assertEquals('mymaster', $options['service']);
        $parameters = $container->getDefinition('webonaute_phpredis.default')->getArgument(0);
        $this->assertEquals('webonaute_phpredis.connection.master_parameters.default', (string)$parameters[0]);
        $masterParameters = $container->getDefinition((string)$parameters[0])->getArgument(0);
        $this->assertEquals('sentinel', $masterParameters['replication']);
        $this->assertEquals('mymaster', $masterParameters['service']);
        $this->assertInternalType('array', $masterParameters['parameters']);
        $this->assertEquals('1', $masterParameters['parameters']['database']);
        $this->assertEquals('pass', $masterParameters['parameters']['password']);

        $this->assertInternalType('array', $container->findTaggedServiceIds('webonaute_phpredis.client'));
        $this->assertEquals(array('webonaute_phpredis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('webonaute_phpredis.client'));
    }

    private function getSentinelYamlConfig(): string
    {
        return <<<'EOF'
clients:
    default:
        type: single
        alias: default
        dsn:
            - redis://localhost?alias=master
            - redis://otherhost
        options:
            replication: sentinel
            service: mymaster
            parameters:
                database: 1
                password: pass
EOF;
    }

    /**
     * Test valid config of the cluster option
     */
    public function testClusterOption(): void
    {
        $this->kernelBoot();
        $extension = new WebonautePhpredisExtension();
        $config = $this->parseYaml($this->getClusterYamlConfig());
        $extension->load(array($config), $container = $this->getContainer());

        $options = $container->getDefinition('webonaute_phpredis.client.default_options')->getArgument(0);
        $this->assertEquals('redis', $options['cluster']);
        $this->assertFalse(array_key_exists('replication', $options));

        $parameters = $container->getDefinition('webonaute_phpredis.default')->getArgument(0);
        $this->assertEquals('webonaute_phpredis.connection.default1_parameters.default', (string)$parameters[0]);
        $this->assertEquals('webonaute_phpredis.connection.default2_parameters.default', (string)$parameters[1]);

        $this->assertInternalType('array', $container->findTaggedServiceIds('webonaute_phpredis.client'));
        $this->assertEquals(array('webonaute_phpredis.default' => array(array('alias' => 'default'))), $container->findTaggedServiceIds('webonaute_phpredis.client'));
    }

    private function getClusterYamlConfig(): string
    {
        return <<<'EOF'
clients:
    default:
        type: single
        alias: default
        dsn:
            - redis://127.0.0.1/1
            - redis://127.0.0.2/2
        options:
            cluster: "redis"
EOF;
    }
}
