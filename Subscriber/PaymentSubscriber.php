<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber;

use Adyen\AdyenException;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\PaymentMethodService as ShopwarePaymentMethodService;
use AdyenPayment\Serializer\Payment\PaymentMethodSerializer;
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
     * @var PaymentMethodSerializer
     */
    private $paymentMethodSerializer;

    /**
     * PaymentSubscriber constructor.
     *
     * @param PaymentMethodService         $paymentMethodService
     * @param ShopwarePaymentMethodService $shopwarePaymentMethodService
     * @param PaymentMethodSerializer      $paymentMethodConverter
     */
    public function __construct(
        PaymentMethodService $paymentMethodService,
        ShopwarePaymentMethodService $shopwarePaymentMethodService,
        PaymentMethodSerializer $paymentMethodConverter
    ) {
        $this->paymentMethodService = $paymentMethodService;
        $this->shopwarePaymentMethodService = $shopwarePaymentMethodService;
        $this->paymentMethodSerializer = $paymentMethodConverter;
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
     *
     * @return array
     * @throws AdyenException
     */
    public function replaceAdyenMethods(Enlight_Event_EventArgs $args): array
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

        $shopwareMethods = array_filter($shopwareMethods, function ($method) {
            return $method['name'] !== AdyenPayment::ADYEN_GENERAL_PAYMENT_METHOD;
        });

        $paymentMethodOptions = $this->shopwarePaymentMethodService->getPaymentMethodOptions();
        if ($paymentMethodOptions['value'] == 0) {
            return $shopwareMethods;
        }
        $adyenPaymentMethods = PaymentMethodCollection::fromAdyenMethods(
            $this->paymentMethodService->getPaymentMethods(
                $paymentMethodOptions['countryCode'],
                $paymentMethodOptions['currency'],
                $paymentMethodOptions['value']
            )
        );

        if (!$adyenPaymentMethods->count()) {
            return $shopwareMethods;
        }

        return ($this->paymentMethodSerializer)($shopwareMethods, $adyenPaymentMethods);
    }
}
