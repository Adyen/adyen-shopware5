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
        $origin = $this->Request()->getPost('origin');

        $context = new PaymentContext();
        $context->setBrowserInfo($browserInfo);
        $context->setOrder($this->adyenManager->fetchOrderIdForCurrentSession());
        $context->setBasket($this->adyenManager->getBasket());
        $context->setPaymentInfo($paymentInfo);
        $context->setShopperInfo($shopperInfo);
        $context->setOrigin($origin);

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

    public function ajaxIdentifyShopperAction()
    {
        $this->Request()->setHeader('Content-Type', 'application/json');
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $fingerprint = $this->Request()->getPost('threeds2_fingerprint');

        $payload = [
            'paymentData' => $this->adyenManager->getPaymentDataSession(),
            'details' => [
                'threeds2.fingerprint' => $fingerprint
            ]
        ];

        $checkout = $this->adyenCheckout->getCheckout();
        $paymentInfo = $checkout->paymentsDetails($payload);
        $this->Response()->setBody(json_encode($paymentInfo));
    }

    public function ajaxChallengeShopperAction()
    {
        $this->Request()->setHeader('Content-Type', 'application/json');
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $challengeResult = $this->Request()->getPost('threeds2_challengeResult');

        $payload = [
            'paymentData' => $this->adyenManager->getPaymentDataSession(),
            'details' => [
                'threeds2.challengeResult' => $challengeResult
            ]
        ];

        $checkout = $this->adyenCheckout->getCheckout();
        $paymentInfo = $checkout->paymentsDetails($payload);
        $this->Response()->setBody(json_encode($paymentInfo));
    }

    /**
     * @return array
     */
    private function getShopperInfo()
    {
        return [
            'shopperIP' => $this->request->getClientIp()
        ];
    }
}
