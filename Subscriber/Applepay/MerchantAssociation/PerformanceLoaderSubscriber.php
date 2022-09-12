<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Applepay\MerchantAssociation;

use Enlight\Event\SubscriberInterface;

final class PerformanceLoaderSubscriber implements SubscriberInterface
{
    /** @var string */
    private $pluginDir;

    public function __construct(string $pluginDir)
    {
        $this->pluginDir = $pluginDir;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Backend_Performance' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Controller_ActionEventArgs $args): void
    {
        $subject = $args->getSubject();
        if ('load' !== $subject->Request()->getActionName()) {
            return;
        }

        $subject->View()->addTemplateDir($this->pluginDir.'/Resources/views/');
        $subject->View()->extendsTemplate('backend/performance/view/applepaymerchantassociation.js');
    }
}
