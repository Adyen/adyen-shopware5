<?php

declare(strict_types=1);

namespace AdyenPayment\Components\CompilerPass;

use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Class NotificationProcessorCompilerPass
 * @package AdyenPayment\Components\CompilerPass
 */
class NotificationProcessorCompilerPass implements CompilerPassInterface
{
    /**
     * @param ContainerBuilder $container
     */
    public function process(ContainerBuilder $container)
    {
        $definition = $container->getDefinition('adyen_payment.components.notification_processor');
        $taggedServices = $container->findTaggedServiceIds('adyen.payment.notificationprocessor');

        foreach ($taggedServices as $id => $tags) {
            $definition->addMethodCall('addProcessor', [new Reference($id)]);
        }
    }
}
