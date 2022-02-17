<?php

use Adyen\AdyenException;
use AdyenPayment\Components\Adyen\PaymentMethodService;
use AdyenPayment\Components\BasketService;
use AdyenPayment\Components\Manager\AdyenManager;
use AdyenPayment\Components\Manager\OrderManager;
use AdyenPayment\Components\Manager\OrderManagerInterface;
use AdyenPayment\Components\OrderMailService;
use AdyenPayment\Models\PaymentResultCodes;
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
    private AdyenManager $adyenManager;
    private PaymentMethodService $adyenCheckout;
    private BasketService $basketService;
    private OrderMailService $orderMailService;
    private Logger $logger;
    private OrderManagerInterface $orderManager;
    private Shopware_Components_Snippet_Manager $snippets;
    private MessageProvider $errorMessageProvider;

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
            /** @var Order $order */
            $order = $this->getModelManager()->getRepository(Order::class)->findOneBy([
                'number' => $response['merchantReference'] ?? '',
            ]);
            $result = $this->validateResponse($response, $order);
            $this->handleReturnResult($result, $order);

            switch(PaymentResultCodes::load($result['resultCode'])) {
                case PaymentResultCodes::authorised():
                case PaymentResultCodes::pending():
                case PaymentResultCodes::received():
                    if (!empty($result['merchantReference'])) {
                        $this->orderMailService->sendOrderConfirmationMail($result['merchantReference']);
                    }
                    $this->redirect([
                        'controller' => 'checkout',
                        'action' => 'finish',
                        'sUniqueID' => $order->getTemporaryId(),
                        'sAGB' => true,
                    ]);
                    break;
                case PaymentResultCodes::cancelled():
                case PaymentResultCodes::error():
                case PaymentResultCodes::refused():
                default:
                    $this->errorMessageProvider->add(
                        $this->snippets->getNamespace('adyen/checkout/error')
                            ->get('errorTransaction'.$result['resultCode'], $result['refusalReason'] ?? '')
                    );

                    if (!empty($result['merchantReference'])) {
                        $this->basketService->cancelAndRestoreByOrderNumber($result['merchantReference']);
                    }

                    $this->redirect([
                        'controller' => 'checkout',
                        'action' => 'confirm',
                    ]);
                    break;
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

        switch (PaymentResultCodes::load($result['resultCode'])) {
            case PaymentResultCodes::authorised():
            case PaymentResultCodes::pending():
            case PaymentResultCodes::received():
                $paymentStatus = $this->getModelManager()->find(
                    Status::class,
                    Status::PAYMENT_STATE_THE_PAYMENT_HAS_BEEN_ORDERED
                );
                break;
            case PaymentResultCodes::cancelled():
            case PaymentResultCodes::error():
            case PaymentResultCodes::refused():
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
            $checkout = $this->adyenCheckout->getCheckout();
            $response = $checkout->paymentsDetails([
                'paymentData' => $this->adyenManager->fetchOrderPaymentData($order),
                'details' => RequestDataFormatter::forPaymentDetails($response),
            ]);
        } catch (AdyenException $e) {
            $response['error'] = $e->getMessage();
        }

        return $response;
    }
}
