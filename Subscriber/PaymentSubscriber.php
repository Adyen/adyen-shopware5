<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\Adyen\PaymentMethodService;
use MeteorAdyen\Components\Configuration;
use MeteorAdyen\Components\PaymentMethodService as ShopwarePaymentMethodService;
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
     * @var ShopwarePaymentMethodService
     */
    private $shopwarePaymentMethodService;

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

        /** @var \sAdmin $subject */
        $subject = $args->get('subject');

        if (!in_array(Shopware()->Front()->Request()->getActionName(), ['shippingPayment', 'payment'])) {
            return $shopwareMethods;
        }

        $shopwareMethods = array_filter($shopwareMethods, function ($method) {
            return $method['name'] !== MeteorAdyen::ADYEN_GENERAL_PAYMENT_METHOD;
        });

        $countryCode = Shopware()->Session()->sOrderVariables['sUserData']['additional']['country']['countryiso'];
        $currency = Shopware()->Session()->sOrderVariables['sBasket']['sCurrencyName'];
        $value = Shopware()->Session()->sOrderVariables['sBasket']['AmountNumeric'];

        $adyenMethods = $this->paymentMethodService->getPaymentMethods($countryCode, $currency, $value);
        $adyenMethods['paymentMethods'] = array_reverse($adyenMethods['paymentMethods']);

        foreach ($adyenMethods['paymentMethods'] as $adyenMethod) {
            $paymentMethodInfo = $this->shopwarePaymentMethodService->getAdyenPaymentInfoByType($adyenMethod['type']);
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