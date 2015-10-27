<?php
/**
 * Created by PhpStorm.
 * User: jiri.bazant
 * Date: 26.10.15
 * Time: 14:31
 */

namespace Symbio\OrangeGate\PageBundle\DependencyInjection\Compiler;


use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Reference;

class CreateSnapshotConsumerCompilerPass implements CompilerPassInterface
{
    /**
     * {@inheritDoc}
     */
    public function process(ContainerBuilder $container)
    {
        if ($container->hasDefinition('sonata.page.notification.create_snapshot')) {
            $definition = $container->getDefinition('sonata.page.notification.create_snapshot');
            $definition->setClass('Symbio\OrangeGate\PageBundle\Consumer\CreateSnapshotConsumer');
        }

        if ($container->hasDefinition('sonata.page.notification.create_snapshots')) {
            $definition = $container->getDefinition('sonata.page.notification.create_snapshots');
            $definition->setClass('Symbio\OrangeGate\PageBundle\Consumer\CreateSnapshotsConsumer');
        }
    }
}