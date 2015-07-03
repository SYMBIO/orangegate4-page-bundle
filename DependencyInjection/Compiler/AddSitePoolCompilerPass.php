<?php

namespace Symbio\OrangeGate\PageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class AddSitePoolCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('sonata.page.admin.shared_block')) {
            $definition = $container->getDefinition('sonata.page.admin.shared_block');
            $definition->addMethodCall('setSitePool', array(new Reference('orangegate.site.pool')));
        }
    }
}
