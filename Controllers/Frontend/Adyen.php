<?php

use MeteorAdyen\Components\Manager\AdyenManager;
use MeteorAdyen\Components\Payload\Chain;
use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\Providers\ApplicationInfoProvider;
use MeteorAdyen\Components\Payload\Providers\BrowserInfoProvider;
use MeteorAdyen\Components\Payload\Providers\OrderInfoProvider;
use MeteorAdyen\Components\Payload\Providers\PaymentMethodProvider;
use MeteorAdyen\Components\Payload\Providers\ShopperInfoProvider;
use MeteorAdyen\Models\PaymentInfo;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

/**
 * Class Shopware_Controllers_Frontend_Adyen
 */
class Shopware_Controllers_Frontend_Adyen extends Shopware_Controllers_Frontend_Payment
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

        $context = $this->createPaymentContext();

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
        $paymentInfo = $checkout->payments($payload, [
            'idempotencyKey' => $context->getTransaction()->getIdempotencyKey()
        ]);

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

    public function resetValidPaymentSessionAction() {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();
        $this->adyenManager->unsetValidPaymentSession();
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
     * @return PaymentContext
     */
    private function createPaymentContext()
    {
        $paymentInfo = json_decode($this->Request()->getPost('paymentMethod') ?? '{}', true);
        $transaction = $this->prepareTransaction();
        $order = $this->prepareOrder($transaction);
        $browserInfo = $this->Request()->getPost('browserInfo');
        $shopperInfo = $this->getShopperInfo();
        $origin = $this->Request()->getPost('origin');

        return new PaymentContext(
            $paymentInfo,
            $order,
            Shopware()->Modules()->Basket(),
            $browserInfo,
            $shopperInfo,
            $origin,
            $transaction
        );
    }

    /**
     * @return PaymentInfo
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function prepareTransaction()
    {
        $transaction = new PaymentInfo();
        $transaction->setOrderId(-1);
        $transaction->setPspReference('');
        $transaction->setIdempotencyKey('');

        $this->getModelManager()->persist($transaction);
        $this->getModelManager()->flush($transaction);

        return $transaction;
    }

    /**
     * @return Order
     */
    private function prepareOrder($transaction)
    {
        $signature = $this->persistBasket();

        $orderNumber = $this->saveOrder(
            $transaction->getId(),
            $signature,
            Status::PAYMENT_STATE_OPEN,
            false
        );

        /** @var Order $order */
        $order = $this->getModelManager()->getRepository(Order::class)->findOneBy([
            'number' => $orderNumber
        ]);

        $transaction->setOrder($order);

        $uuid = \Adyen\Util\Uuid::generateV4();
        $transaction->setIdempotencyKey($uuid);

        $this->getModelManager()->persist($transaction);
        $this->getModelManager()->flush($transaction);

        return $order;
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
