<?php

declare(strict_types=1);

namespace AdyenPayment\Components\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class NotificationProcessorCompilerPass.
 */
class NotificationProcessorCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $definition = $container->getDefinition('AdyenPayment\Components\NotificationProcessor');
        $taggedServices = $container->findTaggedServiceIds('adyen.payment.notificationprocessor');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addProcessor', [new Reference($id)]);
        }
    }
}
