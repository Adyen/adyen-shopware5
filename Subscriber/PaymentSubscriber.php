<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\AdyenException;
use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;

/**
 * Class PaymentSubscriber.
 */
class PaymentSubscriber implements SubscriberInterface
{
    private bool $skipReplaceAdyenMethods = false;
    private EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider;

    /**
     * PaymentSubscriber constructor.
     */
    public function __construct(
        EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider
    ) {
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'enrichAdyenPaymentMethods',
        ];
    }

    /**
     * Replace general Adyen payment method with dynamic loaded payment methods.
     *
     * @throws AdyenException
     */
    public function enrichAdyenPaymentMethods(Enlight_Event_EventArgs $args): array
    {
        $shopwareMethods = $args->getReturn();

        if (!in_array(
            Shopware()->Front()->Request()->getActionName(),
            ['shippingPayment', 'payment'], true
        )
        ) {
            return $shopwareMethods;
        }

        return $this->enrichedPaymentMeanProvider->__invoke(
            PaymentMeanCollection::createFromShopwareArray($shopwareMethods)
        )->toShopwareArray();
    }
}
