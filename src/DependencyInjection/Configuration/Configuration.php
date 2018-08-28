<?php

namespace WebonautePhpredisBundle\DependencyInjection\Configuration;

use Doctrine\Common\Cache\RedisCache;
use Monolog\Handler\RedisHandler;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use WebonautePhpredisBundle\Client\RedisArray;
use WebonautePhpredisBundle\Client\RedisClient;
use WebonautePhpredisBundle\Client\RedisCluster;
use WebonautePhpredisBundle\DataCollector\RedisDataCollector;
use WebonautePhpredisBundle\Logger\RedisLogger;
use WebonautePhpredisBundle\SwiftMailer\RedisSpool;

/**
 * RedisBundle configuration class.
 *
 * @author Henrik Westphal <henrik.westphal@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    private $debug;

    public function __construct($debug)
    {
        $this->debug = (Boolean)$debug;
    }

    /**
     * Generates the configuration tree builder.
     *
     * @return TreeBuilder The tree builder
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('webonaute_phpredis');

        $rootNode
            ->children()
            ->arrayNode('class')
            ->addDefaultsIfNotSet()
            ->children()
            ->scalarNode('single_client')->defaultValue(RedisClient::class)->end()
            ->scalarNode('array_client')->defaultValue(RedisArray::class)->end()
            ->scalarNode('cluster_client')->defaultValue(RedisCluster::class)->end()
            ->scalarNode('logger')->defaultValue(RedisLogger::class)->end()
            ->scalarNode('data_collector')->defaultValue(RedisDataCollector::class)->end()
            ->scalarNode('doctrine_cache_phpredis')->defaultValue(RedisCache::class)->end()
            ->scalarNode('monolog_handler')->defaultValue(RedisHandler::class)->end()
            ->scalarNode('swiftmailer_spool')->defaultValue(RedisSpool::class)->end()
            ->end()
            ->end()
            ->end();

        $this->addClientsSection($rootNode);
        $this->addSessionSection($rootNode);
        $this->addDoctrineSection($rootNode);
        $this->addMonologSection($rootNode);
        $this->addSwiftMailerSection($rootNode);
        $this->addProfilerStorageSection($rootNode);

        return $treeBuilder;
    }

    /**
     * Adds the webonaute_phpredis.clients configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addClientsSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->fixXmlConfig('client')
            ->children()
            ->arrayNode('clients')
            ->isRequired()
            ->requiresAtLeastOneElement()
            ->useAttributeAsKey('alias', false)
            ->prototype('array')
            ->fixXmlConfig('dsn')
            ->children()
            ->scalarNode('type')->isRequired()
            ->validate()
            ->ifNotInArray([
                'single',
                'single',
                'array',
                'cluster',
            ])
            ->thenInvalid('The redis client type %s is invalid.')
            ->end()
            ->end()
            ->scalarNode('alias')->isRequired()->end()
            ->booleanNode('logging')->defaultValue($this->debug)->end()
            ->arrayNode('dsns')
            ->isRequired()
            ->performNoDeepMerging()
            ->beforeNormalization()
            ->ifString()->then(function ($v) {
                return (array)$v;
            })
            ->end()
            ->beforeNormalization()
            ->always()->then(function ($v) {
                return array_map(function ($dsn) {
                    $parsed = new RedisDsn($dsn);

                    return $parsed->isValid() ? $parsed : $dsn;
                }, $v);
            })
            ->end()
            ->prototype('variable')
            ->validate()
            ->ifTrue(function ($v) {
                return \is_string($v);
            })
            ->thenInvalid('The redis DSN %s is invalid.')
            ->end()
            ->end()
            ->end()
            ->scalarNode('alias')->isRequired()->end()
            ->arrayNode('options')
            ->addDefaultsIfNotSet()
            ->children()
            ->booleanNode('connection_async')->defaultFalse()->end()
            ->booleanNode('connection_persistent')->defaultFalse()->end()
            ->scalarNode('connection_timeout')->defaultValue(5)->end()
            ->scalarNode('read_write_timeout')->defaultNull()->end()
            ->booleanNode('iterable_multibulk')->defaultFalse()->end()
            ->booleanNode('throw_errors')->defaultTrue()->end()
            ->scalarNode('profile')->defaultValue('default')
            ->beforeNormalization()
            ->ifTrue(function ($v) {
                return false === \is_string($v);
            })
            ->then(function ($v) {
                return sprintf('%.1F', $v);
            })
            ->end()
            ->end()
            ->scalarNode('cluster')->defaultNull()->end()
            ->scalarNode('prefix')->defaultNull()->end()
            ->scalarNode('serializer')->defaultValue(\Redis::SERIALIZER_PHP)->end()
            ->enumNode('replication')->values([true, false, 'sentinel'])->end()
            ->scalarNode('service')->defaultNull()->end()
            ->arrayNode('parameters')
            ->canBeUnset()
            ->children()
            ->scalarNode('database')->defaultNull()->end()
            ->scalarNode('password')->defaultNull()->end()
            ->booleanNode('logging')->defaultValue($this->debug)->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end()
            ->end();
    }

    /**
     * Adds the webonaute_phpredis.session configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addSessionSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
            ->arrayNode('session')
            ->canBeUnset()
            ->children()
            ->scalarNode('client')->isRequired()->end()
            ->scalarNode('prefix')->defaultValue('session')->end()
            ->scalarNode('ttl')->end()
            ->booleanNode('locking')->defaultTrue()->end()
            ->scalarNode('spin_lock_wait')->defaultValue(150000)->end()
            ->end()
            ->end()
            ->end();
    }

    /**
     * Adds the webonaute_phpredis.doctrine configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addDoctrineSection(ArrayNodeDefinition $rootNode): void
    {
        $doctrineNode = $rootNode->children()->arrayNode('doctrine')->canBeUnset();
        foreach (['metadata_cache', 'result_cache', 'query_cache', 'second_level_cache'] as $type) {
            $doctrineNode
                ->children()
                ->arrayNode($type)
                ->canBeUnset()
                ->children()
                ->scalarNode('enabled')->defaultFalse()->end()
                ->scalarNode('client')->isRequired()->end()
                ->scalarNode('namespace')->defaultNull()->end()
                ->end()
                ->fixXmlConfig('entity_manager')
                ->children()
                ->arrayNode('entity_managers')
                ->defaultValue([])
                ->beforeNormalization()->ifString()->then(function ($v) {
                    return (array)$v;
                })->end()
                ->prototype('scalar')->end()
                ->end()
                ->end()
                ->fixXmlConfig('document_manager')
                ->children()
                ->arrayNode('document_managers')
                ->defaultValue([])
                ->beforeNormalization()->ifString()->then(function ($v) {
                    return (array)$v;
                })->end()
                ->prototype('scalar')->end()
                ->end()
                ->end()
                ->end()
                ->end();
        }
    }

    /**
     * Adds the webonaute_phpredis.monolog configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addMonologSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
            ->arrayNode('monolog')
            ->canBeUnset()
            ->children()
            ->scalarNode('client')->isRequired()->end()
            ->scalarNode('key')->isRequired()->end()
            ->scalarNode('formatter')->end()
            ->end()
            ->end()
            ->end();
    }

    /**
     * Adds the webonaute_phpredis.swiftmailer configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addSwiftMailerSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
            ->arrayNode('swiftmailer')
            ->canBeUnset()
            ->children()
            ->scalarNode('client')->isRequired()->end()
            ->scalarNode('key')->isRequired()->end()
            ->end()
            ->end()
            ->end();
    }

    /**
     * Adds the webonaute_phpredis.profiler_storage configuration
     *
     * @param ArrayNodeDefinition $rootNode
     */
    private function addProfilerStorageSection(ArrayNodeDefinition $rootNode): void
    {
        $rootNode
            ->children()
            ->arrayNode('profiler_storage')
            ->canBeUnset()
            ->children()
            ->scalarNode('client')->isRequired()->end()
            ->scalarNode('ttl')->isRequired()->end()
            ->end()
            ->end()
            ->end();
    }
}
