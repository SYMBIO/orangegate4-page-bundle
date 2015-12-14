<?php

namespace Symbio\OrangeGate\PageBundle\DependencyInjection\Compiler;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class SetSnapshotAdminTemplateCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('sonata.page.admin.snapshot')) {
            $definition = $container->getDefinition('sonata.page.admin.snapshot');
            $definition->addMethodCall('setTemplate', array('list','SymbioOrangeGatePageBundle:SnapshotAdmin:list.html.twig'));
        }
    }
}
