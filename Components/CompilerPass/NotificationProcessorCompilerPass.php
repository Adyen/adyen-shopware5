<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\CompilerPass;

use MeteorAdyen\Components\NotificationProcessor;
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
        if (!$container->has(NotificationProcessor::class)) {
            return;
        }

        $definition = $container->findDefinition(NotificationProcessor::class);

        $taggedServices = $container->findTaggedServiceIds(NotificationProcessor\NotificationProcessorInterface::class);

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('setProcessors', [new Reference($id)]);
        }
    }
}