<?php

use MeteorAdyen\Components\Manager\AdyenManager;
use MeteorAdyen\Components\Payload\Chain;
use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\Providers\ApplicationInfoProvider;
use MeteorAdyen\Components\Payload\Providers\BrowserInfoProvider;
use MeteorAdyen\Components\Payload\Providers\OrderInfoProvider;
use MeteorAdyen\Components\Payload\Providers\PaymentMethodProvider;
use MeteorAdyen\Components\Payload\Providers\ShopperInfoProvider;
use Shopware\Models\Order\Order;

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
        $order = $this->adyenManager->fetchOrderForCurrentSession();
        $browserInfo = $this->Request()->getPost('browserInfo');
        $shopperInfo = $this->getShopperInfo();
        $origin = $this->Request()->getPost('origin');

        $context = new PaymentContext(
            $paymentInfo,
            $order,
            $this->adyenManager->getBasket(),
            $browserInfo,
            $shopperInfo,
            $origin
        );

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
        $paymentInfo = $checkout->payments($payload, ['idempotencyKey' => $order->getAttribute()->getMeteorAdyenIdempotencyKey()]);

        $this->adyenManager->storePaymentDataInSession($paymentInfo['paymentData']);

        $this->Response()->setBody(json_encode($paymentInfo));
    }
    
    public function ajaxIdentifyShopperAction()
    {
        $this->paymentDetails('threeds2_fingerprint', 'threeds2.fingerprint');
    }

    public function ajaxChallengeShopperAction()
    {
        $this->paymentDetails('threeds2_challengeResult', 'threeds2.challengeResult');
    }

    /**
     * @param $post
     * @param $detail
     * @throws \Adyen\AdyenException
     */
    private function paymentDetails($post, $detail)
    {
        $this->Request()->setHeader('Content-Type', 'application/json');
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $postData = $this->Request()->getPost($post);

        $payload = [
            'paymentData' => $this->adyenManager->getPaymentDataSession(),
            'details' => [
                $detail => $postData
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
