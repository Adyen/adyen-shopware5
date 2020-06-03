<?php

use MeteorAdyen\Components\Manager\AdyenManager;
use MeteorAdyen\Models\Enum\PaymentResultCodes;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\Logger;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

/**
 * Class Redirect
 */
class Shopware_Controllers_Frontend_Process extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * @var AdyenManager
     */
    private $adyenManager;

    /**
     * @var \MeteorAdyen\Components\Adyen\PaymentMethodService
     */
    private $adyenCheckout;

    /**
     * @var \MeteorAdyen\Components\BasketService
     */
    private $basketService;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Whitelist notifyAction
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return'];
    }


    public function preDispatch()
    {
        $this->adyenManager = $this->get('meteor_adyen.components.manager.adyen_manager');
        $this->adyenCheckout = $this->get('meteor_adyen.components.adyen.payment.method');
        $this->basketService = $this->get('meteor_adyen.components.basket_service');
        $this->logger = $this->get('meteor_adyen.logger');
    }

    /**
     * @throws Exception
     */
    public function returnAction()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $response = $this->Request()->getParams();

        if ($response) {
            $result = $this->validateResponse($response);
            $this->handleReturnResult($result);

            switch ($result['resultCode']) {
                case PaymentResultCodes::AUTHORISED:
                case PaymentResultCodes::PENDING:
                case PaymentResultCodes::RECEIVED:
                    $this->redirect([
                        'controller' => 'checkout',
                        'action' => 'finish',
                        'sAGB' => true
                    ]);
                    break;
                case PaymentResultCodes::CANCELLED:
                case PaymentResultCodes::ERROR:
                case PaymentResultCodes::REFUSED:
                default:

                    $this->basketService->cancelAndRestoreByOrderNumber($result['merchantReference']);

                    $this->redirect([
                        'controller' => 'checkout',
                        'action' => 'confirm'
                    ]);
                    break;
            }
        }
    }

    /**
     * @param $result
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function handleReturnResult($result)
    {
        $orderNumber = $result['merchantReference'];
        /** @var Order $order */
        $order = $this->getModelManager()->getRepository(Order::class)->findOneBy([
            'number' => $orderNumber
        ]);

        if (!$order) {
            $this->logger->error('No order found for ', [
                'ordernumber' => $orderNumber,
            ]);

            return;
        }

        switch ($result['resultCode']) {
            case 'Authorised':
            case 'Pending':
            case 'Received':
                $paymentStatus = $this->getModelManager()->find(Status::class, Status::PAYMENT_STATE_THE_PAYMENT_HAS_BEEN_ORDERED);
                break;
            case 'Cancelled':
            case 'Error':
            case 'Fail':
            case 'Refused':
                $paymentStatus = $this->getModelManager()->find(Status::class, Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED);
                break;
            default:
                $paymentStatus = $this->getModelManager()->find(Status::class, Status::PAYMENT_STATE_REVIEW_NECESSARY);
                break;
        }

        $order->setPaymentStatus($paymentStatus);
        $order->setTransactionId($result['pspReference']);
        $this->getModelManager()->persist($order);
    }

    /**
     * Validates the payload from checkout /payments hpp and returns the api response
     *
     * @param $response
     * @return mixed
     */
    private function validateResponse($response)
    {
        $request['paymentData'] = $this->adyenManager->getPaymentDataSession();
        $request['details'] = $response;

        try {
            $checkout = $this->adyenCheckout->getCheckout();
            $response = $checkout->paymentsDetails($request);
        } catch (\Adyen\AdyenException $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}
