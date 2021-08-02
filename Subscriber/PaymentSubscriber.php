<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\AdyenException;
use AdyenPayment\Components\Adyen\PaymentMethod\EnrichedPaymentMeanProviderInterface;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;

/**
 * Class PaymentSubscriber
 *
 * @package AdyenPayment\Subscriber
 */
class PaymentSubscriber implements SubscriberInterface
{
    /**
     * @var $skipReplaceAdyenMethods
     */
    private $skipReplaceAdyenMethods = false;
    /**
     * @var EnrichedPaymentMeanProviderInterface
     */
    private $enrichedPaymentMeanProvider;

    /**
     * PaymentSubscriber constructor.
     *
     * @param EnrichedPaymentMeanProviderInterface $enrichedPaymentMeanProvider
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
            'Shopware_Controllers_Frontend_Checkout::getSelectedPayment::before' => 'beforeGetSelectedPayment',
        ];
    }

    /**
     * Skip replacement of payment methods during checkPaymentAvailability validation
     */
    public function beforeGetSelectedPayment()
    {
        $this->skipReplaceAdyenMethods = true;
    }

    /**
     * Replace general Adyen payment method with dynamic loaded payment methods
     *
     * @param Enlight_Event_EventArgs $args
     *
     * @return array
     * @throws AdyenException
     */
    public function enrichAdyenPaymentMethods(Enlight_Event_EventArgs $args): array
    {
        $shopwareMethods = $args->getReturn();

        if ($this->skipReplaceAdyenMethods
            || !in_array(
                Shopware()->Front()->Request()->getActionName(),
                ['shippingPayment', 'payment']
            )
        ) {
            $this->skipReplaceAdyenMethods = false;

            return $shopwareMethods;
        }

        return $this->enrichedPaymentMeanProvider->__invoke($shopwareMethods);
    }
}
