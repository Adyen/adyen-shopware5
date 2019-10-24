<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\Adyen\PaymentMethodService;
use MeteorAdyen\Components\PaymentMethodService as ShopwarePaymentMethodService;
use MeteorAdyen\MeteorAdyen;
use Shopware\Components\Model\ModelManager;

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
     * @var bool
     */
    private $isDisabled;

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
        $this->isDisabled = false;
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

        $adyenMethods = $this->paymentMethodService->getPaymentMethods();

        foreach ($adyenMethods['paymentMethods'] as $adyenMethod) {
            $paymentMethodInfo = $this->shopwarePaymentMethodService->getAdyenPaymentInfoByType($adyenMethod['type']);
            $shopwareMethods[] = [
                'id' => "adyen_" . $adyenMethod['type'],
                'name' => $adyenMethod['type'],
                'description' => $paymentMethodInfo->getName(),
                'additionaldescription' => $paymentMethodInfo->getDescription(),
            ];
        }

        return $shopwareMethods;
    }
}