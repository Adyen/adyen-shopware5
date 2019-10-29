<?php

use MeteorAdyen\Components\Manager\AdyenManager;
use MeteorAdyen\Components\Payload\Chain;
use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\Providers\BrowserInfoProvider;
use MeteorAdyen\Components\Payload\Providers\OrderInfoProvider;
use MeteorAdyen\Components\Payload\Providers\PaymentMethodProvider;
use MeteorAdyen\Components\Payload\Providers\ShopperInfoProvider;
use MeteorAdyen\Models\Payload\Providers\ApplicationInfoProvider;

/**
 * Class Shopware_Controllers_Frontend_Adyen
 */
class Shopware_Controllers_Frontend_Adyen extends Enlight_Controller_Action
{
    /**
     * @var AdyenManager
     */
    private $adyenManager;

    /**
     * @var \MeteorAdyen\Components\Adyen\PaymentMethodService
     */
    private $adyenCheckout;

    public function preDispatch()
    {
        $this->adyenManager = $this->get('meteor_adyen.components.manager.adyen_manager');
        $this->adyenCheckout = $this->get('meteor_adyen.components.adyen.payment.method');
    }

    public function ajaxDoPaymentAction()
    {
        $this->Request()->setHeader('Content-Type', 'application/json');
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $paymentInfo = json_decode($this->Request()->getPost('paymentMethod') ?? '{}', true);
        $browserInfo = $this->Request()->getPost('browserInfo');
        $shopperInfo = $this->getShopperInfo();

        $context = new PaymentContext();
        $context->setBrowserInfo($browserInfo);
        $context->setOrder($this->adyenManager->fetchOrderIdForCurrentSession());
        $context->setBasket($this->adyenManager->getBasket());
        $context->setPaymentInfo($paymentInfo);
        $context->setShopperInfo($shopperInfo);

        $chain = new Chain(
            new ApplicationInfoProvider(),
            new ShopperInfoProvider(),
            new OrderInfoProvider(),
            new PaymentMethodProvider(),
            // new LineItemsInfoProvider(),
            new BrowserInfoProvider()
        );

        $payload = $chain->provide($context);
        $checkout = $this->adyenCheckout->getCheckout();
        $paymentInfo = $checkout->payments($payload);

        $this->adyenManager->storePaymentDataInSession($paymentInfo['paymentData']);

        $this->Response()->setBody(json_encode($paymentInfo));
    }

    private function getShopperInfo()
    {
        return [
            'shopperIP' => $this->request->getClientIp()
        ];
    }
}
