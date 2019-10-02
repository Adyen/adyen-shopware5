<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\Adyen\PaymentMethodService;
use MeteorAdyen\MeteorAdyen;

/**
 * Class PaymentSubscriber
 * @package MeteorAdyen\Subscriber
 */
class PaymentSubscriber implements SubscriberInterface
{
    /**
     * @var PaymentMethodService
     */
    protected $paymentMethodService;

    /**
     * PaymentSubscriber constructor.
     * @param PaymentMethodService $paymentMethodService
     */
    public function __construct(
        PaymentMethodService $paymentMethodService
    )
    {
        $this->paymentMethodService = $paymentMethodService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'replaceAdyenMethods',
        ];
    }

    /**
     * Replace general Adyen payment method with dynamic loaded payment methods
     *
     * @param Enlight_Event_EventArgs $args
     * @return array
     * @throws AdyenException
     */
    public function replaceAdyenMethods(Enlight_Event_EventArgs $args): array
    {
        $shopwareMethods = $args->getReturn();

        foreach ($shopwareMethods as $k => $method) {
            if ($method['name'] === MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD) {
                unset($shopwareMethods[$k]);
            }
        }

        $adyenMethods = $this->paymentMethodService->getPaymentMethods();

        foreach ($adyenMethods['paymentMethods'] as $adyenMethod) {
            $shopwareMethods[] = [
                'id' => "adyen_" . $adyenMethod['type'],
                'name' => $adyenMethod['type'],
                'description' => $adyenMethod['name'],
            ];
        }

        return $shopwareMethods;
    }
}