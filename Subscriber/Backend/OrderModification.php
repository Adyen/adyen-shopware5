<?php

namespace AdyenPayment\Subscriber\Backend;

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\Domain\AuthorizationAdjustment\Validator\AuthorizationAdjustmentValidator;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Models\TransactionHistory;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionHistoryService;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use AdyenPayment\Utilities\Plugin;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use Exception;
use Shopware\Models\Order\Order;
use Shopware_Controllers_Backend_Order;

/**
 * Class OrderModification.
 *
 * @package AdyenPayment\Subscriber\Backend
 */
class OrderModification implements SubscriberInterface
{

    /** @var string[]  */
    private const SUPPORTED_ACTIONS = ['deletePosition', 'savePosition', 'save'];

    /**
     * @var OrderRepository
     */
    private $orderRepository;

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
        return ['Enlight_Controller_Action_PostDispatchSecure_Backend_Order' => '__invoke'];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     *
     * @return void
     *
     * @throws Exception
     */
    public function __invoke(Enlight_Event_EventArgs $args): void
    {
        /** @var Shopware_Controllers_Backend_Order $subject */
        $subject = $args->getSubject();

        $actionName = $subject->Request()->getActionName();

        if (!in_array($actionName, self::SUPPORTED_ACTIONS, true)) {
            return;
        }

        $orderId = $args->getSubject()->View()->getAssign('data')['orderId'] ?? $args->getSubject()->View()
            ->getAssign('data')['id'];

        if(!$orderId){
            return;
        }

        $order = $this->tryToGetShopOrderWithAdyenPayment($orderId);

        if (!$order) {
            return;
        }

        StoreContext::doWithStore((string)$order->getShop()->getId(), function () use ($order) {
            if (!self::shouldSendOrderModification($order)) {
                return;
            }
            AdminAPI::get()->authorizationAdjustment((string)$order->getShop()->getId())
                ->handleOrderModification(
                    $order->getTemporaryId(),
                    $order->getInvoiceAmount(),
                    $order->getCurrency());
        });
    }

    /**
     * @param Order $order
     *
     * @return bool
     */
    private static function shouldSendOrderModification(Order $order): bool
    {
        try {
            $transactionHistory = self::getTransactionHistory($order);
            $amount = Amount::fromFloat(
                $order->getInvoiceAmount(),
                Currency::fromIsoCode($order->getCurrency())
            );
            AuthorizationAdjustmentValidator::validateAdjustmentPossibility($transactionHistory);
            AuthorizationAdjustmentValidator::validateModificationPossibility($transactionHistory, $amount->minus($transactionHistory->getCapturedAmount()));

            return true;
        } catch (Exception $exception) {
            return false;
        }
    }

    /**
     * @param Order $order
     *
     * @return TransactionHistory
     *
     * @throws Exception
     */
    private static function getTransactionHistory(Order $order): TransactionHistory
    {
        return StoreContext::doWithStore(
            (string)$order->getShop()->getId(),
            [self::getTransactionHistoryService((string)$order->getShop()->getId()), 'getTransactionHistory'],
            [$order->getTemporaryId()]
        );
    }

    /**
     * @param string $storeId
     *
     * @return TransactionHistoryService
     *
     * @throws Exception
     */
    private static function getTransactionHistoryService(string $storeId): TransactionHistoryService
    {
        return StoreContext::doWithStore(
            $storeId,
            [ServiceRegister::getInstance(), 'getService'],
            [TransactionHistoryService::class]
        );
    }

    /**
     * @param int $orderId
     *
     * @return Order|null
     */
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
}
