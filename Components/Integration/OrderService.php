<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use Adyen\Core\BusinessLogic\Domain\Integration\Order\OrderService as OrderServiceInterface;
use Shopware_Components_Modules;
use sOrder;

/**
 * Class OrderService
 *
 * @package AdyenPayment\Components\Integration
 */
class OrderService implements OrderServiceInterface
{
    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var Shopware_Components_Modules
     */
    private $modules;

    /**
     * @param OrderRepository $orderRepository
     * @param Shopware_Components_Modules $modules
     */
    public function __construct(OrderRepository $orderRepository, Shopware_Components_Modules $modules)
    {
        $this->orderRepository = $orderRepository;
        $this->modules = $modules;
    }

    /**
     * @inheritDoc
     */
    public function orderExists(string $merchantReference): bool
    {
        $order = $this->orderRepository->getOrderByTemporaryId($merchantReference);

        return !empty($order) && $order->getShop()->getId() === (int)StoreContext::getInstance()->getStoreId();
    }

    /**
     * @inheritDoc
     */
    public function updateOrderStatus(Webhook $webhook, string $statusId): void
    {
        $order = $this->orderRepository->getOrderByTemporaryId($webhook->getMerchantReference());

        if (!$order) {
            return;
        }

        /** @var sOrder $sOrder */
        $sOrder = $this->modules->getModule('sOrder');
        $sOrder->setPaymentStatus($order->getId(), $statusId);

        if ($order->getTransactionId() === $order->getTemporaryId()) {
            $originalReference = $webhook->getPspReference();
            $order->setTransactionId($originalReference);
            $this->orderRepository->updateOrder($order);
        }
    }

    /**
     * @param string $merchantReference
     *
     * @return string
     */
    public function getOrderCurrency(string $merchantReference): string
    {
        $order = $this->orderRepository->getOrderByTemporaryId($merchantReference);

        return $order ? $order->getCurrency() : '';
    }

    /**
     * @param string $merchantReference
     *
     * @return string
     */
    public function getOrderUrl(string $merchantReference): string
    {
        $order = $this->orderRepository->getOrderByTemporaryId($merchantReference);

        return $order ? $this->getOrderUrlForId((int)$order->getId()) : 'javascript:';
    }

    public function getOrderUrlForId(int $orderId): string
    {
        return implode('', [
            'javascript:' .
            'postMessageApi.openModule({' .
            "name: 'Shopware.apps.Order', " .
            "action: 'detail', " .
            "params: {" .
            "orderId: {$orderId}" .
            "}" .
            "}) && undefined;"
        ]);
    }
}
