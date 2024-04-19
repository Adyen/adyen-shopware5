<?php

namespace AdyenPayment\Subscriber\Backend;

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\CurrencyMismatchException;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Exceptions\InvalidCurrencyCode;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Models\CaptureType;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Exceptions\InvalidMerchantReferenceException;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Models\TransactionHistory;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionHistoryService;
use Adyen\Core\Infrastructure\ServiceRegister;
use Adyen\Webhook\EventCodes;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use AdyenPayment\Utilities\Plugin;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Enlight_Hook_HookArgs;
use Exception;
use Shopware\Models\Order\Order;

/**
 * Class OrderUpdate
 *
 * @package AdyenPayment\Subscriber\Backend
 */
final class OrderUpdate implements SubscriberInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var TransactionHistoryService
     */
    private $historyService;

    /**
     * @param OrderRepository $orderRepository
     */
    public function __construct(OrderRepository $orderRepository)
    {
        $this->orderRepository = $orderRepository;
    }

    /**
     * @inheritDoc
     */
    public static function getSubscribedEvents(): array
    {
        return [
            'Shopware_Controllers_Backend_OrderState_Notify' => 'backendUpdate',
            'sOrder::setPaymentStatus::after' => 'paymentStatusUpdate'
        ];
    }

    /**
     * @param Enlight_Hook_HookArgs $args
     *
     * @return void
     *
     * @throws CurrencyMismatchException
     * @throws InvalidCurrencyCode
     * @throws InvalidMerchantReferenceException
     */
    public function paymentStatusUpdate(Enlight_Hook_HookArgs $args): void
    {
        $orderId = $args->get('orderId') ?? 0;
        $paymentStatusId = (int)$args->get('paymentStatusId') ?? 0;

        $this->process($orderId, $paymentStatusId);
    }

    /**
     * @throws InvalidMerchantReferenceException
     * @throws InvalidCurrencyCode
     * @throws Exception
     */
    public function backendUpdate(Enlight_Event_EventArgs $args): void
    {
        $orderId = $args->get('id') ?? 0;
        $paymentStatusId = $args->get('status') ?? 0;

        $this->process($orderId, $paymentStatusId);
    }

    /**
     * @param int $orderId
     * @param int $paymentStatusId
     *
     * @return void
     *
     * @throws InvalidCurrencyCode
     * @throws InvalidMerchantReferenceException
     * @throws CurrencyMismatchException
     * @throws Exception
     */
    private function process(int $orderId, int $paymentStatusId)
    {
        $order = $this->tryToGetShopOrderWithAdyenPayment($orderId);
        if (!$order) {
            return;
        }

        $storeId = (string)$order->getShop()->getId();
        $generalSettings = AdminAPI::get()->generalSettings($storeId)->getGeneralSettings();
        /** @var TransactionHistory $transactionHistory */
        $transactionHistory = StoreContext::doWithStore(
            $storeId,
            [$this->getService(), 'getTransactionHistory'],
            [$order->getTemporaryId()]
        );
        $authorisedAmount = $transactionHistory->getAuthorizedAmount();
        $cancelledAmount = $transactionHistory->getTotalAmountForEventCode(EventCodes::CANCELLATION);
        $capturedAmount = $transactionHistory->getCapturedAmount();

        if ($generalSettings->toArray()['capture'] !== CaptureType::MANUAL || $generalSettings->toArray()['shipmentStatus'] !== (string)$paymentStatusId || $authorisedAmount->getPriceInCurrencyUnits() === $capturedAmount->plus($cancelledAmount)->getPriceInCurrencyUnits()) {
            return;
        }

        AdminAPI::get()->capture($storeId)->handle(
            $order->getTemporaryId(),
            $authorisedAmount->minus($cancelledAmount->plus($capturedAmount))->getPriceInCurrencyUnits(),
            $order->getCurrency()
        );
    }

    private function tryToGetShopOrderWithAdyenPayment(int $orderId): ?Order
    {
        try {
            $order = $this->orderRepository->getOrderById($orderId);

            if (!$order || !Plugin::isAdyenPaymentMean($order->getPayment()->getName())) {
                return null;
            }

            return $order;
        } catch (\Throwable $exception) {
            return null;
        }
    }

    /**
     * @return TransactionHistoryService
     */
    private function getService(): TransactionHistoryService
    {
        if ($this->historyService === null) {
            $this->historyService = ServiceRegister::getService(TransactionHistoryService::class);
        }

        return $this->historyService;
    }
}
