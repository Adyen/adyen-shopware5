<?php

use Adyen\AdyenException;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\BasketService;
use AdyenPayment\Components\Manager\AdyenManager;
use AdyenPayment\Components\Manager\OrderManager;
use AdyenPayment\Components\Manager\OrderManagerInterface;
use AdyenPayment\Components\OrderMailService;
use AdyenPayment\Models\PaymentResultCode;
use AdyenPayment\Session\ErrorMessageProvider;
use AdyenPayment\Session\MessageProvider;
use AdyenPayment\Utils\RequestDataFormatter;
use Shopware\Components\CSRFWhitelistAware;
use Shopware\Components\Logger;
use Shopware\Models\Order\Order;
use Shopware\Models\Order\Status;

//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Shopware_Controllers_Frontend_Process extends Shopware_Controllers_Frontend_Payment implements CSRFWhitelistAware
{
    /** @var AdyenManager */
    private $adyenManager;

    /** @var PaymentMethodService */
    private $adyenCheckout;

    /** @var BasketService */
    private $basketService;

    /** @var OrderMailService */
    private $orderMailService;

    /** @var Logger */
    private $logger;

    /** @var OrderManagerInterface */
    private $orderManager;

    /** @var Shopware_Components_Snippet_Manager */
    private $snippets;

    /** @var MessageProvider */
    private $errorMessageProvider;

    /**
     * Whitelist notifyAction
     *
     * @return string[]
     *
     * @psalm-return array{0: 'return'}
     */
    public function getWhitelistedCSRFActions()
    {
        return ['return'];
    }

    /**
     * @return void
     */
    public function preDispatch()
    {
        $this->adyenManager = $this->get(AdyenManager::class);
        $this->adyenCheckout = $this->get(PaymentMethodService::class);
        $this->basketService = $this->get(BasketService::class);
        $this->orderMailService = $this->get(OrderMailService::class);
        $this->logger = $this->get('adyen_payment.logger');
        $this->orderManager = $this->get(OrderManager::class);
        $this->snippets = $this->get('snippets');
        $this->errorMessageProvider = $this->get(ErrorMessageProvider::class);
    }

    /**
     * @throws Exception
     */
    public function returnAction(): void
    {
        $this->Front()->Plugins()->ViewRenderer()->setNoRender();

        $response = $this->Request()->getParams();

        if ($response) {
            $merchantReference = !empty($response['merchantReference']) ? $response['merchantReference'] : '';

            $order = $this->getOrderByMerchantReference($merchantReference);
            $result = $this->validateResponse($response, $order);

            // Make the best effort to obtain merchant reference
            if (empty($merchantReference) && !empty($result['merchantReference'])) {
                $merchantReference = $result['merchantReference'];
            }

            // Make the best effort to obtain related shop order
            if (!$order && !empty($merchantReference)) {
                $order = $this->getOrderByMerchantReference($merchantReference);
            }

            $this->handleReturnResult($result, $order);

            switch(PaymentResultCode::load($result['resultCode'])) {
                case PaymentResultCode::authorised():
                case PaymentResultCode::pending():
                case PaymentResultCode::received():
                    if (!empty($merchantReference)) {
                        $this->orderMailService->sendOrderConfirmationMail($merchantReference);
                    }
                    $this->redirect([
                        'controller' => 'checkout',
                        'action' => 'finish',
                        'sUniqueID' => $order ? $order->getTemporaryId() : '',
                        'sAGB' => true,
                    ]);
                    break;
                case PaymentResultCode::cancelled():
                case PaymentResultCode::error():
                case PaymentResultCode::refused():
                default:
                    $this->errorMessageProvider->add(
                        $this->snippets->getNamespace('adyen/checkout/error')
                            ->get('errorTransaction'.$result['resultCode'], $result['refusalReason'] ?? '')
                    );

                    if (!empty($merchantReference)) {
                        $this->basketService->cancelAndRestoreByOrderNumber($merchantReference);
                    }

                    $this->redirect([
                        'controller' => 'checkout',
                        'action' => 'confirm',
                    ]);
                    break;
            }

            if ($order) {
                $this->orderManager->save($order);
            }
        }
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    private function handleReturnResult(array $result, ?Order $order): void
    {
        if (!$order) {
            $this->logger->error('No order found for ', [
                'ordernumber' => $result['merchantReference'] ?? '',
            ]);

            return;
        }

        switch (PaymentResultCode::load($result['resultCode'])) {
            case PaymentResultCode::authorised():
            case PaymentResultCode::pending():
            case PaymentResultCode::received():
                $paymentStatus = $this->getModelManager()->find(
                    Status::class,
                    Status::PAYMENT_STATE_THE_PAYMENT_HAS_BEEN_ORDERED
                );
                break;
            case PaymentResultCode::cancelled():
            case PaymentResultCode::error():
            case PaymentResultCode::refused():
                $paymentStatus = $this->getModelManager()->find(
                    Status::class,
                    Status::PAYMENT_STATE_THE_PROCESS_HAS_BEEN_CANCELLED
                );
                break;
            default:
                $paymentStatus = $this->getModelManager()->find(Status::class, Status::PAYMENT_STATE_REVIEW_NECESSARY);
                break;
        }

        $this->orderManager->updatePayment(
            $order,
            (string) ($result['pspReference'] ?? ''),
            $paymentStatus
        );
    }

    /**
     * Validates the payload from checkout /payments hpp and returns the api response
     *
     * @return mixed
     */
    private function validateResponse(array $response, ?Order $order)
    {
        try {
            $request = [
                'details' => RequestDataFormatter::forPaymentDetails($response),
            ];

            $paymentData = $this->adyenManager->fetchOrderPaymentData($order);
            if (!empty($paymentData)) {
                $request['paymentData'] = $paymentData;
            }

            $checkout = $this->adyenCheckout->getCheckout();
            $response = $checkout->paymentsDetails($request);
        } catch (AdyenException $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }

    private function getOrderByMerchantReference($merchantReference): ?Order
    {
        return $this->getModelManager()->getRepository(Order::class)->findOneBy([
            'number' => $merchantReference,
        ]);
    }
}
