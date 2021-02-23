<?php

use AdyenPayment\Models\Enum\PaymentResultCodes;
use AdyenPayment\Utils\RequestDataFormatter;
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
     * @var \AdyenPayment\Components\Manager\AdyenManager
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
     * @var \AdyenPayment\Components\Manager\OrderManagerInterface
     */
    private $orderManager;


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
        $this->orderManager = $this->get('AdyenPayment\Components\Manager\OrderManager');
    }

    /**
     * @throws Exception
     */
    public function returnAction()
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $response = $this->Request()->getParams();

        if ($response) {
            /** @var Order $order */
            $order = $this->getModelManager()->getRepository(Order::class)->findOneBy([
                'number' => $response['merchantReference'] ?? ''
            ]);
            $result = $this->validateResponse($response, $order);
            $this->handleReturnResult($result, $order);

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
     * @param array $result
     * @param Order|null $order
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function handleReturnResult(array $result, $order)
    {
        if (!$order) {
            $this->logger->error('No order found for ', [
                'ordernumber' => $result['merchantReference'] ?? '',
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

        $this->orderManager->updateOrderPayment(
            $order,
            (string) ($result['pspReference'] ?? ''),
            $paymentStatus
        );
    }

    /**
     * Validates the payload from checkout /payments hpp and returns the api response
     *
     * @param array $response
     * @param Order $order
     * @return mixed
     */
    private function validateResponse($response, $order)
    {
        $request['paymentData'] = $this->adyenManager->fetchOrderPaymentData($order);
        $request['details'] = RequestDataFormatter::forPaymentDetails($response);

        try {
            $checkout = $this->adyenCheckout->getCheckout();
            $response = $checkout->paymentsDetails($request);
        } catch (\Adyen\AdyenException $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}
