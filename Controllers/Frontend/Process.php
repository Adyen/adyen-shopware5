<?php

use AdyenPayment\Components\Manager\AdyenManager;
use AdyenPayment\Models\Enum\PaymentResultCodes;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\Logger;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

/**
 * Class Redirect
 */
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Shopware_Controllers_Frontend_Process extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /**
     * @var AdyenManager
     */
    private $adyenManager;

    /**
     * @var \AdyenPayment\Components\Adyen\PaymentMethodService
     */
    private $adyenCheckout;

    /**
     * @var \AdyenPayment\Components\BasketService
     */
    private $basketService;

	/**
	 * @var \AdyenPayment\Components\OrderMailService
	 */
	private $orderMailService;

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
        $this->adyenManager = $this->get('adyen_payment.components.manager.adyen_manager');
        $this->adyenCheckout = $this->get('adyen_payment.components.adyen.payment.method');
		$this->basketService = $this->get('adyen_payment.components.basket_service');
		$this->orderMailService = $this->get('adyen_payment.components.order_mail_service');
        $this->logger = $this->get('adyen_payment.logger');
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
					if (!empty($result['merchantReference'])) {
						$this->orderMailService->sendOrderConfirmationMail($result['merchantReference']);
					}
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
                    if (!empty($result['merchantReference'])) {
                        $this->basketService->cancelAndRestoreByOrderNumber($result['merchantReference']);
                    }
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
            case PaymentResultCodes::AUTHORISED:
            case PaymentResultCodes::PENDING:
            case PaymentResultCodes::RECEIVED:
                $paymentStatus = $this->getModelManager()->find(
                    Status::class,
                    Status::PAYMENT_STATE_THE_PAYMENT_HAS_BEEN_ORDERED
                );
                break;
            case PaymentResultCodes::CANCELLED:
            case PaymentResultCodes::ERROR:
            case PaymentResultCodes::REFUSED:
                $paymentStatus = $this->getModelManager()->find(
                    Status::class,
                    Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED
                );
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
