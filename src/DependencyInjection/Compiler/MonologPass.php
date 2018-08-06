<?php

namespace WebonautePhpredisBundle\DependencyInjection\Compiler;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class MonologPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container): void
    {
        $serviceId = 'webonaute_phpredis.monolog.handler';
        if ($container->hasDefinition($serviceId)) {
            $handlerDefinition = $container->getDefinition($serviceId);
            $configuration = $container->getExtension('monolog')->getConfiguration(array(), $container);
            $processor = new Processor();
            $config = $processor->processConfiguration($configuration, $container->getExtensionConfig('monolog'));
            foreach ($config['handlers'] as $handler) {
                if (isset($handler['id']) && $serviceId === $handler['id']) {
                    if (isset($handler['level'])) {
                        $level = $handler['level'] = \is_int($handler['level']) ? $handler['level'] : \constant('Monolog\Logger::'.strtoupper($handler['level']));
                        $handlerDefinition->addArgument($level);
                    }
                    if (isset($handler['bubble'])) {
                        $handlerDefinition->addArgument($handler['bubble']);
                    }

                    return;
                }
            }
        }
    }
}
