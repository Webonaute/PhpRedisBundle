<?php

namespace WebonautePhpredisBundle;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use WebonautePhpredisBundle\DependencyInjection\Compiler\LoggingPass;
use WebonautePhpredisBundle\DependencyInjection\Compiler\MonologPass;

/**
 * WebonautePhpredisBundle
 */
class WebonautePhpredisBundle extends Bundle
{

    /**
     * @param \Symfony\Component\DependencyInjection\ContainerBuilder $container
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new LoggingPass());
        $container->addCompilerPass(new MonologPass());
    }
}
