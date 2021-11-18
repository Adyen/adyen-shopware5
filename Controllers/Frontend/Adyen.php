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

    /**
     * @return void
     */
    public function preDispatch()
    {
        $this->adyenManager = $this->get('adyen_payment.components.manager.adyen_manager');
        $this->adyenCheckout = $this->get('adyen_payment.components.adyen.payment.method');
        $this->basketService = $this->get('adyen_payment.components.basket_service');
        $this->logger = $this->get('adyen_payment.logger');
        $this->paymentPayloadProvider = $this->get('adyen_payment.components.payload.payment_payload_provider');
    }

    public function ajaxDoPaymentAction(): void
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
                    'sUniqueID' => $context->getOrder()->getTemporaryId(),
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
     *
     * @deprecated will be removed in 3.0.0 to move closer to a generic implementation,
     * use paymentDetailsAction() instead
     */
    public function ajaxThreeDsAction(): void
    {
        $threeDSResult = (string) ($this->Request()->getPost()['details']['threeDSResult'] ?? '');
        if ('' === $threeDSResult) {
            $this->logger->error('3DS missing data', [
                'action' => $this->Request()->getPost()['action'] ?? '',
                'threeDSResult' => substr($threeDSResult, -5),
                'paymentData' => substr( $this->Request()->getPost()['paymentData'] ?? '', -5),
            ]);
        }

        $this->paymentDetailsAction();
    }

    /**
     * @throws AdyenException
     */
    public function paymentDetailsAction(): void
    {
        $this->Request()->setHeader('Content-Type', 'application/json');
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $payload = array_intersect_key($this->Request()->getPost(), ['details' => true]);
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
    private function createPaymentContext(): PaymentContext
    {
        $paymentInfo = json_decode($this->Request()->getPost('paymentMethod') ?? '{}', true);
        $transaction = $this->prepareTransaction();
        $order = $this->prepareOrder($transaction);
        $browserInfo = $this->Request()->getPost('browserInfo');
        $shopperInfo = $this->getShopperInfo();
        $origin = $this->Request()->getPost('origin');
        $storePaymentMethod = (bool) json_decode($this->Request()->getPost('storePaymentMethod', false), true);

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
    private function prepareTransaction(): PaymentInfo
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
    private function prepareOrder(PaymentInfo $transaction): Order
    {
        $signature = $this->persistBasket();

        Shopware()->Session()->offsetSet(
            AdyenPayment::SESSION_ADYEN_RESTRICT_EMAILS,
            0 < $transaction->getId()
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
     *
     * @psalm-return array{shopperIP: mixed}
     */
    private function getShopperInfo(): array
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
    private function handlePaymentData($paymentInfo): void
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
    private function handlePaymentDataError($paymentInfo): void
    {
        if ($paymentInfo['merchantReference']) {
            $this->basketService->cancelAndRestoreByOrderNumber($paymentInfo['merchantReference']);
        }
    }
}
