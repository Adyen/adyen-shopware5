<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Applepay\MerchantAssociation;

use Enlight\Event\SubscriberInterface;

final class RegisterUrlCountSubscriber implements SubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Controllers_Seo_filterCounts' => '__invoke',
        ];
    }

    public function __invoke(\Enlight_Event_EventArgs $args): array
    {
        return array_merge($args->getReturn(), [
            'applepaymerchantassociation' => 1, // 1: same URL for each shop
        ]);
    }
}
