<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use AdyenPayment\Serializer\PaymentMeanCollectionSerializer;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;

final class EnrichPaymentSubscriber implements SubscriberInterface
{
    /** @var EnrichedPaymentMeanProviderInterface */
    private $enrichedPaymentMeanProvider;

    /** @var PaymentMeanCollectionSerializer */
    private $serializer;

    public function __construct(
        EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider,
        PaymentMeanCollectionSerializer $serializer
    ) {
        $this->enrichedPaymentMeanProvider = $enrichedPaymentMeanProvider;
        $this->serializer = $serializer;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => '__invoke',
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(Enlight_Event_EventArgs $args): array
    {
        $shopwareMethods = $args->getReturn();
        if (!in_array(Shopware()->Front()->Request()->getActionName(), ['shippingPayment', 'payment'], true)) {
            return $shopwareMethods;
        }

        return ($this->serializer)(
            ($this->enrichedPaymentMeanProvider)(
                PaymentMeanCollection::createFromShopwareArray($shopwareMethods)
            )
        );
    }
}
