<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\PaymentMethodService as ShopwarePaymentMethodService;
use AdyenPayment\AdyenPayment;

/**
 * Class PaymentSubscriber
 * @package AdyenPayment\Subscriber
 */
class PaymentSubscriber implements SubscriberInterface
{
    /**
     * @var PaymentMethodService
     */
    protected $paymentMethodService;
    /**
     * @var ShopwarePaymentMethodService
     */
    private $shopwarePaymentMethodService;

    /**
     * @var $skipReplaceAdyenMethods
     */
    private $skipReplaceAdyenMethods = false;

    /**
     * PaymentSubscriber constructor.
     * @param PaymentMethodService $paymentMethodService
     * @param ShopwarePaymentMethodService $shopwarePaymentMethodService
     */
    public function __construct(
        PaymentMethodService $paymentMethodService,
        ShopwarePaymentMethodService $shopwarePaymentMethodService
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->shopwarePaymentMethodService = $shopwarePaymentMethodService;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_Modules_Admin_GetPaymentMeans_DataFilter' => 'replaceAdyenMethods',
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
     * @return array
     * @throws AdyenException
     */
    public function replaceAdyenMethods(Enlight_Event_EventArgs $args): array
    {
        $shopwareMethods = $args->getReturn();

        if ($this->skipReplaceAdyenMethods || !in_array(
            Shopware()->Front()->Request()->getActionName(),
            ['shippingPayment', 'payment']
        )) {
            $this->skipReplaceAdyenMethods = false;
            return $shopwareMethods;
        }

        $shopwareMethods = array_filter($shopwareMethods, function ($method) {
            return $method['name'] !== AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD;
        });

        $paymentMethodOptions = $this->shopwarePaymentMethodService->getPaymentMethodOptions();

        $adyenMethods = $this->paymentMethodService->getPaymentMethods(
            $paymentMethodOptions['countryCode'],
            $paymentMethodOptions['currency'],
            $paymentMethodOptions['value']
        );

        if (empty($adyenMethods)) {
            return $shopwareMethods;
        }

        $adyenMethods['paymentMethods'] = array_reverse($adyenMethods['paymentMethods']);

        foreach ($adyenMethods['paymentMethods'] as $adyenMethod) {
            $paymentMethodInfo = $this->shopwarePaymentMethodService->getAdyenPaymentInfoByType(
                $adyenMethod['type'],
                $adyenMethods['paymentMethods']
            );
            array_unshift($shopwareMethods, [
                'id' => Configuration::PAYMENT_PREFIX . $adyenMethod['type'],
                'name' => $adyenMethod['type'],
                'description' => $paymentMethodInfo->getName(),
                'additionaldescription' => $paymentMethodInfo->getDescription(),
                'image' => $this->shopwarePaymentMethodService->getAdyenImage($adyenMethod),
            ]);
        }

        return $shopwareMethods;
    }
}
