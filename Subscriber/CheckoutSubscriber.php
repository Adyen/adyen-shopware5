<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber;

use Adyen\AdyenException;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\Adyen\PaymentMethodService;
use MeteorAdyen\Components\Configuration;
use Shopware_Controllers_Frontend_Checkout;

/**
 * Class CheckoutSubscriber
 * @package MeteorAdyen\Subscriber
 */
class CheckoutSubscriber implements SubscriberInterface
{
    /**
     * @var ConfigurationService
     */
    protected $configuration;

    /**
     * @var PaymentMethodService
     */
    protected $paymentMethodService;


    public function __construct(
        Configuration $configuration,
        PaymentMethodService $paymentMethodService
    ) {
        $this->configuration = $configuration;
        $this->paymentMethodService = $paymentMethodService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Action_PostDispatch_Frontend_Checkout' => 'onCheckout'
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     * @throws AdyenException
     */
    public function onCheckout(Enlight_Event_EventArgs $args)
    {
        /** @var Shopware_Controllers_Frontend_Checkout $subject */
        $subject = $args->getSubject();

        if (!in_array($subject->Request()->getActionName(), ['shippingPayment'])) {
            return;
        }
        $view = $subject->View();

        $countrycode = $view->getAssign('sUserData')['additional']['country']['countryiso'];
        $currency = $view->getAssign('sBasket')['sCurrencyName'];
        $value = $view->getAssign('sBasket')['AmountNumeric'];
        $paymentMethods = $this->paymentMethodService->getPaymentMethods($countrycode, $currency, $value);

        $adyenConfig = [
            "originKey" => $this->configuration->getOriginKey(),
            "environment" => $this->configuration->getEnvironment(),
            "paymentMethods" => json_encode($paymentMethods),
            "paymentMethodPrefix" => $this->configuration->getPaymentMethodPrefix(),
        ];

        $subject->View()->assign('sAdyenConfig', $adyenConfig);
    }
}