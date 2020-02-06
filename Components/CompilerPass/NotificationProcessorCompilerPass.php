<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class NotificationProcessorCompilerPass
 * @package MeteorAdyen\Components\CompilerPass
 */
class NotificationProcessorCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('meteor_adyen.components.notification_processor');
        $taggedServices = $container->findTaggedServiceIds('meteor.adyen.notificationprocessor');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addProcessor', [new Reference($id)]);
        }
    }
}