<?php

use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\BasketService;
use AdyenPayment\Components\Calculator\PriceCalculationService;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Components\Manager\AdyenManager;
use AdyenPayment\Components\Payload\Chain;
use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\Providers\ApplicationInfoProvider;
use AdyenPayment\Components\Payload\Providers\BrowserInfoProvider;
use AdyenPayment\Components\Payload\Providers\LineItemsInfoProvider;
use AdyenPayment\Components\Payload\Providers\OrderInfoProvider;
use AdyenPayment\Components\Payload\Providers\PaymentMethodProvider;
use AdyenPayment\Components\Payload\Providers\ShopperInfoProvider;
use AdyenPayment\Models\PaymentInfo;
use Shopware\Components\Logger;
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
     * @var PaymentMethodService
     */
    private $adyenCheckout;

    /**
     * @var BasketService
     */
    private $basketService;

    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PriceCalculationService
     */
    private $priceCalculationService;

    public function preDispatch()
    {
        $this->adyenManager = $this->get('adyen_payment.components.manager.adyen_manager');
        $this->adyenCheckout = $this->get('adyen_payment.components.adyen.payment.method');
        $this->basketService = $this->get('adyen_payment.components.basket_service');
        $this->configuration = $this->get('adyen_payment.components.configuration');
        $this->logger = $this->get('adyen_payment.logger');
        $this->priceCalculationService = $this->get('adyen_payment.components.calculator.price_calculation_service');
    }

    public function ajaxDoPaymentAction()
    {
        $this->Request()->setHeader('Content-Type', 'application/json');
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $context = $this->createPaymentContext();

        $chain = new Chain(
            new ApplicationInfoProvider($this->configuration),
            new ShopperInfoProvider(),
            new OrderInfoProvider(),
            new PaymentMethodProvider(),
            new LineItemsInfoProvider($this->priceCalculationService),
            new BrowserInfoProvider()
        );

        try {
            $payload = $chain->provide($context);
            $checkout = $this->adyenCheckout->getCheckout();
            $paymentInfo = $checkout->payments($payload);

            $this->adyenManager->storePaymentDataInSession($paymentInfo['paymentData']);
            $this->handlePaymentData($paymentInfo);
            $this->Response()->setBody(json_encode(
                [
                    'status' => 'success',
                    'content' => $paymentInfo
                ]
            ));
        } catch (\Adyen\AdyenException $e) {
            $this->logger->debug($e);
            $this->Response()->setBody(json_encode(
                [
                    'status' => 'error',
                    'content' => $e->getMessage()
                ]
            ));
        }
    }

    /**
     * @throws \Adyen\AdyenException
     */
    public function ajaxIdentifyShopperAction()
    {
        $this->paymentDetails('threeds2_fingerprint', 'threeds2.fingerprint');
    }

    /**
     * @throws \Adyen\AdyenException
     */
    public function ajaxChallengeShopperAction()
    {
        $this->paymentDetails('threeds2_challengeResult', 'threeds2.challengeResult');
    }

    public function resetValidPaymentSessionAction()
    {
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
        $this->handlePaymentData($paymentInfo);
        $this->Response()->setBody(json_encode($paymentInfo));
    }

    /**
     * @return PaymentContext
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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

        $this->getModelManager()->persist($transaction);
        $this->getModelManager()->flush($transaction);

        return $transaction;
    }

    /**
     * @param $transaction
     * @return Order
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
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


    /**
     * @param $paymentInfo
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handlePaymentData($paymentInfo)
    {
        if (!in_array(
            $paymentInfo['resultCode'],
            ['Authorised', 'IdentifyShopper', 'ChallengeShopper', 'RedirectShopper']
        )) {
            $this->handlePaymentDataError($paymentInfo);
        }
    }

    /**
     * @param $paymentInfo
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Zend_Db_Adapter_Exception
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function handlePaymentDataError($paymentInfo)
    {
        if ($paymentInfo['merchantReference']) {
            $this->basketService->cancelAndRestoreByOrderNumber($paymentInfo['merchantReference']);
        }
    }
}
