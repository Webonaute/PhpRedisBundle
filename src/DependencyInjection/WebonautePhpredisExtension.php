<?php

namespace WebonautePhpredisBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Exception\LogicException;
use Symfony\Component\DependencyInjection\Loader\XmlFileLoader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use WebonautePhpredisBundle\DependencyInjection\Configuration\Configuration;
use WebonautePhpredisBundle\DependencyInjection\Configuration\RedisDsn;
use WebonautePhpredisBundle\Session\Storage\Handler\RedisSessionHandler;
use WebonautePhpredisBundle\Pool\Pool;

//use WebonautePhpredisBundle\DependencyInjection\Configuration\RedisEnvDsn;

/**
 * WebonautePhpredisExtension
 */
class WebonautePhpredisExtension extends Extension
{
    /**
     * Loads the configuration.RedisLoggedClient
     *
     * @param array $configs An array of configurations
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws InvalidConfigurationException
     * @throws \Exception
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $loader = new XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('redis.xml');

        $mainConfig = $this->getConfiguration($configs, $container);
        $config = $this->processConfiguration($mainConfig, $configs);

        foreach ($config['class'] as $name => $class) {
            $container->setParameter(sprintf('webonaute_phpredis.%s.class', $name), $class);
        }

        foreach ($config['clients'] as $client) {
            $this->loadClient($client, $container);
        }

        if (isset($config['session'])) {
            $this->loadSession($config, $container, $loader);
        }

        if (isset($config['doctrine']) && \count($config['doctrine'])) {
            $this->loadDoctrine($config, $container);
        }

        if (isset($config['monolog'])) {
            if (!empty($config['clients'][$config['monolog']['client']]['logging'])) {
                throw new InvalidConfigurationException(
                    sprintf('You have to disable logging for the client "%s" that you have configured under "webonaute_phpredis.monolog.client"', $config['monolog']['client'])
                );
            }
            $this->loadMonolog($config, $container);
        }

        if (isset($config['swiftmailer'])) {
            $this->loadSwiftMailer($config, $container);
        }

        if (isset($config['profiler_storage'])) {
            $this->loadProfilerStorage($config, $container, $loader);
        }
    }

    public function getConfiguration(array $config, ContainerBuilder $container)
    {
        return new Configuration($container->getParameter('kernel.debug'));
    }

    /**
     * {@inheritdoc}
     */
    public function getNamespace(): string
    {
        return 'http://symfony.com/schema/dic/redis';
    }

    /**
     * {@inheritdoc}
     */
    public function getXsdValidationBasePath(): string
    {
        return __DIR__ . '/../Resources/config/schema';
    }

    /**
     * Loads a redis client.
     *
     * @param array $client A client configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadClient(array $client, ContainerBuilder $container): void
    {
        switch ($client['type']) {
            case 'cluster':
                $this->loadPhpredisCluster($client, $container);
                break;
            case 'array':
                //$this->loadPhpredisClient($client, $container);
                break;
            case 'single':
                $this->loadPhpredisClient($client, $container);
                break;
        }
    }

    /**
     * Loads a redis client using phpredis.
     *
     * @param array $client A client configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \RuntimeException
     */
    protected function loadPhpredisCluster(array $client, ContainerBuilder $container): void
    {
        $connectionCount = \count($client['dsns']);

        if (1 !== $connectionCount) {
            throw new \RuntimeException('Support for RedisArray is not yet implemented.');
        }

        $seeds = [];
        /** @var RedisDsn $dsn */
        foreach ($client['dsns'] as $dsn) {
            if ($dsn instanceof RedisDsn) {
                if (null !== $dsn->getSocket()) {
                    $seeds[] = $dsn->getSocket();
                } else {
                    $seeds[] = $dsn->getHost() . ':' . $dsn->getPort();
                }
            }
        }

        /** @var \WebonautePhpredisBundle\DependencyInjection\Configuration\RedisDsn $dsn */
        $phpredisId = sprintf('webonaute_phpredis.phpredis.%s', $client['alias']);

        $phpredisDef = new Definition($container->getParameter('webonaute_phpredis.' . $client['type'] . '_client.class'));
        $phpredisDef->addArgument($client['alias']);
        $phpredisDef->addArgument($seeds);
        $phpredisDef->addArgument($client['options']['connection_timeout']);
        $phpredisDef->addArgument($client['options']['read_write_timeout']);
        $phpredisDef->addArgument($client['options']['connection_persistent']);

        if ($client['logging']) {
            $phpredisDef->addArgument(['alias' => $client['alias']]);
            $phpredisDef->addArgument(new Reference('webonaute_phpredis.logger'));
        }

        $phpredisDef->addTag('webonaute_phpredis.client', ['alias' => $client['alias']]);
        $phpredisDef->setPublic(false);
        $phpredisDef->setLazy(true);

        if ($client['options']['prefix']) {
            $phpredisDef->addMethodCall('setOption', [\Redis::OPT_PREFIX, $client['options']['prefix']]);
        }
        if ($client['options']['serializer']) {
            $phpredisDef->addMethodCall('setOption', [\Redis::OPT_SERIALIZER, $client['options']['serializer']]);
        }
        if (null !== $dsn->getPassword()) {
            $phpredisDef->addMethodCall('auth', [$dsn->getPassword()]);
        }
        if (null !== $dsn->getDatabase()) {
            throw new LogicException('Session locking feature is currently not supported in in context of Redis Cluster.');
        }
        $container->setDefinition($phpredisId, $phpredisDef);

        $container->setAlias(sprintf('webonaute_phpredis.%s', $client['alias']), $phpredisId);
        $container->setAlias(sprintf('webonaute_phpredis.%s_client', $client['alias']), $phpredisId);

        $container->getDefinition(Pool::class)->addMethodCall('set', [$client['alias'], new Reference($phpredisId)]);
    }

    /**
     * Loads a redis client using phpredis.
     *
     * @param array $client A client configuration
     * @param ContainerBuilder $container A ContainerBuilder instance
     *
     * @throws \RuntimeException
     */
    protected function loadPhpredisClient(array $client, ContainerBuilder $container): void
    {
        $connectionCount = \count($client['dsns']);

        if (1 !== $connectionCount) {
            throw new \RuntimeException('Support for RedisArray is not yet implemented.');
        }

        $dsn = $client['dsns'][0];

        /** @var \WebonautePhpredisBundle\DependencyInjection\Configuration\RedisDsn $dsn */
        $phpredisId = sprintf('webonaute_phpredis.phpredis.%s', $client['alias']);

        $phpredisDef = new Definition($container->getParameter('webonaute_phpredis.' . $client['type'] . '_client.class'));
        if ($client['logging']) {
            $phpredisDef->addArgument(['alias' => $client['alias']]);
            $phpredisDef->addArgument(new Reference('webonaute_phpredis.logger'));
        }

        $phpredisDef->addTag('webonaute_phpredis.client', ['alias' => $client['alias']]);
        $phpredisDef->setPublic(false);
        $phpredisDef->setLazy(true);

        $connectMethod = $client['options']['connection_persistent'] ? 'pconnect' : 'connect';
        $connectParameters = [];
        if ($dsn instanceof RedisDsn) {
            if (null !== $dsn->getSocket()) {
                $connectParameters[] = $dsn->getSocket();
                $connectParameters[] = null;
            } else {
                $connectParameters[] = $dsn->getHost();
                $connectParameters[] = $dsn->getPort();
            }
            if ($client['options']['connection_timeout']) {
                $connectParameters[] = $client['options']['connection_timeout'];
            } else {
                $connectParameters[] = null;
            }
            if ($client['options']['connection_persistent']) {
                $connectParameters[] = $dsn->getPersistentId();
            }
        }

        $phpredisDef->addMethodCall($connectMethod, $connectParameters);
        if ($client['options']['prefix']) {
            $phpredisDef->addMethodCall('setOption', [\Redis::OPT_PREFIX, $client['options']['prefix']]);
        }
        if ($client['options']['serializer']) {
            $phpredisDef->addMethodCall('setOption', [\Redis::OPT_SERIALIZER, $client['options']['serializer']]);
        }
        if (null !== $dsn->getPassword()) {
            $phpredisDef->addMethodCall('auth', [$dsn->getPassword()]);
        }
        if (null !== $dsn->getDatabase()) {
            $phpredisDef->addMethodCall('select', [$dsn->getDatabase()]);
        }
        $container->setDefinition($phpredisId, $phpredisDef);

        $container->setAlias(sprintf('webonaute_phpredis.%s', $client['alias']), $phpredisId);
        $container->setAlias(sprintf('webonaute_phpredis.%s_client', $client['alias']), $phpredisId);

        $container->getDefinition(Pool::class)->addMethodCall('set', [$client['alias'], new Reference($phpredisId)]);
    }

    /**
     * Loads the session configuration.
     *
     * @param array $config A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader $loader A XmlFileLoader instance
     *
     * @throws \Exception
     */
    protected function loadSession(array $config, ContainerBuilder $container, XmlFileLoader $loader): void
    {
        $loader->load('session.xml');

        $container->setParameter('webonaute_phpredis.session.client', $config['session']['client']);
        $container->setParameter('webonaute_phpredis.session.prefix', $config['session']['prefix']);
        $container->setParameter('webonaute_phpredis.session.locking', $config['session']['locking']);
        $container->setParameter('webonaute_phpredis.session.spin_lock_wait', $config['session']['spin_lock_wait']);

        $client = $container->getParameter('webonaute_phpredis.session.client');
        $prefix = $container->getParameter('webonaute_phpredis.session.prefix');
        $locking = $container->getParameter('webonaute_phpredis.session.locking');
        $spinLockWait = $container->getParameter('webonaute_phpredis.session.spin_lock_wait');

        $client = sprintf('webonaute_phpredis.%s_client', $client);
        $container->setAlias('webonaute_phpredis.session.client', $client);

        if (isset($config['session']['ttl'])) {
            $definition = $container->getDefinition(RedisSessionHandler::class);
            $definition->addMethodCall('setTtl', [$config['session']['ttl']]);
        }
    }

    /**
     * Loads the Doctrine configuration.
     *
     * @param array $config A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadDoctrine(array $config, ContainerBuilder $container): void
    {
        foreach ($config['doctrine'] as $name => $cache) {
            if ('second_level_cache' === $name) {
                $name = 'second_level_cache.region_cache_driver';
            }

            $definitionFunction = function ($client, $cache) use ($container) {
                $def = new Definition($container->getParameter('webonaute_phpredis.doctrine_cache_phpredis.class'));
                $def->addMethodCall('setRedis', [$client]);
                if ($cache['namespace']) {
                    $def->addMethodCall('setNamespace', [$cache['namespace']]);
                }

                return $def;
            };

            $client = new Reference(sprintf('webonaute_phpredis.%s_client', $cache['client']));
            foreach ($cache['entity_managers'] as $em) {
                $def = $definitionFunction($client, $cache);
                $container->setDefinition(sprintf('doctrine.orm.%s_%s', $em, $name), $def);
            }
            foreach ($cache['document_managers'] as $dm) {
                $def = $definitionFunction($client, $cache);
                $container->setDefinition(sprintf('doctrine_mongodb.odm.%s_%s', $dm, $name), $def);
            }
        }
    }

    /**
     * Loads the Monolog configuration.
     *
     * @param array $config A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadMonolog(array $config, ContainerBuilder $container): void
    {
        if ('phpredis' === $config['clients'][$config['monolog']['client']]['type']) {
            $ref = new Reference(sprintf('webonaute_phpredis.phpredis.%s', $config['monolog']['client']));
        } else {
            $ref = new Reference(sprintf('webonaute_phpredis.%s', $config['monolog']['client']));
        }

        $def = new Definition(
            $container->getParameter('webonaute_phpredis.monolog_handler.class'), [
                $ref,
                $config['monolog']['key'],
            ]
        );

        $def->setPublic(false);
        if (!empty($config['monolog']['formatter'])) {
            $def->addMethodCall('setFormatter', [new Reference($config['monolog']['formatter'])]);
        }
        $container->setDefinition('webonaute_phpredis.monolog.handler', $def);
    }

    /**
     * Loads the SwiftMailer configuration.
     *
     * @param array $config A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     */
    protected function loadSwiftMailer(array $config, ContainerBuilder $container): void
    {
        $def = new Definition($container->getParameter('webonaute_phpredis.swiftmailer_spool.class'));
        $def->setPublic(false);
        $def->addMethodCall('setRedis', [new Reference(sprintf('webonaute_phpredis.%s', $config['swiftmailer']['client']))]);
        $def->addMethodCall('setKey', [$config['swiftmailer']['key']]);
        $container->setDefinition('webonaute_phpredis.swiftmailer.spool', $def);
        $container->setAlias('swiftmailer.spool.redis', 'webonaute_phpredis.swiftmailer.spool');
    }

    /**
     * Loads the profiler storage configuration.
     *
     * @param array $config A configuration array
     * @param ContainerBuilder $container A ContainerBuilder instance
     * @param XmlFileLoader $loader A XmlFileLoader instance
     *
     * @throws \Exception
     */
    protected function loadProfilerStorage(array $config, ContainerBuilder $container, XmlFileLoader $loader): void
    {
        $loader->load('profiler_storage.xml');

        $container->setParameter('webonaute_phpredis.profiler_storage.client', $config['profiler_storage']['client']);
        $container->setParameter('webonaute_phpredis.profiler_storage.ttl', $config['profiler_storage']['ttl']);

        $client = $container->getParameter('webonaute_phpredis.profiler_storage.client');
        $client = sprintf('webonaute_phpredis.%s_client', $client);
        $container->setAlias('webonaute_phpredis.profiler_storage.client', $client);
    }
}
