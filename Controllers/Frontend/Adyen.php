<?php

use Adyen\AdyenException;
use AdyenPayment\AdyenPayment;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\BasketService;
use AdyenPayment\Components\Manager\AdyenManager;
use AdyenPayment\Components\Payload\Chain;
use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Models\PaymentInfo;
use Shopware\Components\Logger;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

/**
 * Class Shopware_Controllers_Frontend_Adyen
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
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
     * @var Logger
     */
    private $logger;

    /**
     * @var Chain
     */
    private $paymentPayloadProvider;

    public function preDispatch()
    {
        $this->adyenManager = $this->get('adyen_payment.components.manager.adyen_manager');
        $this->adyenCheckout = $this->get('adyen_payment.components.adyen.payment.method');
        $this->basketService = $this->get('adyen_payment.components.basket_service');
        $this->logger = $this->get('adyen_payment.logger');
        $this->paymentPayloadProvider = $this->get('adyen_payment.components.payload.payment_payload_provider');
    }

    public function ajaxDoPaymentAction()
    {
        $this->Request()->setHeader('Content-Type', 'application/json');
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $context = $this->createPaymentContext();

        try {
            $payload = $this->paymentPayloadProvider->provide($context);
            $checkout = $this->adyenCheckout->getCheckout();
            $paymentInfo = $checkout->payments($payload);

            $this->adyenManager->storePaymentData(
                $context->getTransaction(),
                $paymentInfo['paymentData'] ?? ''
            );
            $this->handlePaymentData($paymentInfo);
            $this->Response()->setBody(json_encode(
                [
                    'status' => 'success',
                    'content' => $paymentInfo,
                ]
            ));
        } catch (AdyenException $ex) {
            $this->logger->debug('AdyenException during doPayment', [
                'message' => $ex->getMessage(),
                'file' => $ex->getFile(),
                'line' => $ex->getLine(),
            ]);

            $this->Response()->setBody(json_encode(
                [
                    'status' => 'error',
                    'content' => $ex->getMessage(),
                ]
            ));
        }
    }

    /**
     * @throws AdyenException
     */
    public function ajaxIdentifyShopperAction()
    {
        $this->paymentDetails('threeds2_fingerprint', 'threeds2.fingerprint');
    }

    /**
     * @throws AdyenException
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
     * @throws AdyenException
     */
    private function paymentDetails(string $post, string $detail)
    {
        $this->Request()->setHeader('Content-Type', 'application/json');
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $postData = $this->Request()->getPost();
        $threeDsDetail = (string) ($postData['details'][$detail] ?? $postData['details'][$post] ?? '');
        $paymentData = (string) $postData['paymentData'] ?? '';
        if (!$threeDsDetail || !$paymentData) {
            $this->logger->error('3DS2 missing data', [
                $detail => substr($threeDsDetail, -5),
                'paymentData' => substr($paymentData, -5),
            ]);
        }

        $payload = [
            'paymentData' => $paymentData,
            'details' => [
                $detail => $threeDsDetail,
            ],
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
        $storePaymentMethod = (bool)json_decode($this->Request()->getPost('storePaymentMethod', false), true);

        return new PaymentContext(
            $paymentInfo,
            $order,
            Shopware()->Modules()->Basket(),
            $browserInfo,
            $shopperInfo,
            $origin,
            $transaction,
            $storePaymentMethod
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
     * @param PaymentInfo $transaction
     *
     * @return Order
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     */
    private function prepareOrder($transaction)
    {
        $signature = $this->persistBasket();

        Shopware()->Session()->offsetSet(
            AdyenPayment::SESSION_ADYEN_RESTRICT_EMAILS,
            (bool)(0 < $transaction->getId())
        );

        Shopware()->Session()->offsetSet(
            AdyenPayment::SESSION_ADYEN_PAYMENT_INFO_ID,
            $transaction->getId()
        );

        if ($this->Request()->getParam('sComment') !== null) {
            Shopware()->Session()->offsetSet('sComment', $this->Request()->getParam('sComment'));
        }

        $orderNumber = $this->saveOrder(
            $transaction->getId(),
            $signature,
            Status::PAYMENT_STATE_OPEN,
            false
        );

        Shopware()->Session()->offsetSet(AdyenPayment::SESSION_ADYEN_RESTRICT_EMAILS, false);

        /** @var Order $order */
        $order = $this->getModelManager()->getRepository(Order::class)->findOneBy([
            'number' => $orderNumber,
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
            'shopperIP' => $this->request->getClientIp(),
        ];
    }


    /**
     * @param $paymentInfo
     *
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
        )
        ) {
            $this->handlePaymentDataError($paymentInfo);
        }
    }

    /**
     * @param $paymentInfo
     *
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
