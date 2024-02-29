<?php

namespace AdyenPayment\Components\Integration;

use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\BusinessLogic\Domain\Integration\Payment\ShopPaymentService;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\Payment\Services\PaymentService;
use Adyen\Core\BusinessLogic\Domain\Webhook\Models\Webhook;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Repositories\Wrapper\OrderRepository;
use Adyen\Core\BusinessLogic\Domain\Integration\Order\OrderService as OrderServiceInterface;
use AdyenPayment\Utilities\Plugin;
use Doctrine\ORM\OptimisticLockException;
use Exception;
use Shopware_Components_Modules;
use sOrder;

/**
 * Class OrderService
 *
 * @package AdyenPayment\Components\Integration
 */
class OrderService implements OrderServiceInterface
{
    /** @var string */
    private const APPLE_PAY = 'applepay';

    /**
     * @var OrderRepository
     */
    private $orderRepository;

    /**
     * @var Shopware_Components_Modules
     */
    private $modules;

    /**
     * @var PaymentMethodService|null
     */
    private $paymentMethodService;

    /**
     * @param OrderRepository $orderRepository
     * @param Shopware_Components_Modules $modules
     */
    public function __construct(
        OrderRepository $orderRepository,
        Shopware_Components_Modules $modules
    ) {
        $this->orderRepository = $orderRepository;
        $this->modules = $modules;
    }

    /**
     * @inheritDoc
     * @throws Exception
     */
    public function orderExists(string $merchantReference): bool
    {
        $order = $this->orderRepository->getOrderByTemporaryId($merchantReference);

        if (empty($order)) {
            throw new Exception('Order with cart ID: ' . $merchantReference . ' still not created.');
        }

        return $order->getShop()->getId() === (int)StoreContext::getInstance()->getStoreId();
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

    /**
     * @inheritDoc
     *
     * @throws OptimisticLockException
     */
    public function updateOrderPayment(Webhook $webhook): void
    {
        $order = $this->orderRepository->getOrderByTemporaryId($webhook->getMerchantReference());
        if (!$order) {
            return;
        }
        $orderPaymentMean = $order->getPayment();
        $methodName = $webhook->getPaymentMethod();
        if (in_array($methodName, PaymentService::CREDIT_CARD_BRANDS, true)) {
            $methodName = PaymentService::CREDIT_CARD_CODE;
        }

        if (str_contains($methodName, self::APPLE_PAY)) {
            $methodName = self::APPLE_PAY;
        }

        $paymentMean = $this->getPaymentMethodService()->getPaymentMeanByName($methodName);

        if (!$paymentMean) {
            return;
        }

        if (Plugin::isAdyenPaymentMean($orderPaymentMean->getName()) &&
            $paymentMean->getId() === $orderPaymentMean->getId()) {
            return;
        }

        $this->orderRepository->setOrderPayment((int)$webhook->getMerchantReference(), $paymentMean);
    }

    /**
     * @return ShopPaymentService
     */
    private function getPaymentMethodService(): ShopPaymentService
    {
        if (!$this->paymentMethodService) {
            $this->paymentMethodService = ServiceRegister::getService(ShopPaymentService::class);
        }

        return $this->paymentMethodService;
    }
}
