<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload;

use AdyenPayment\Models\PaymentInfo;
use sBasket;
use Shopware\Models\Order\Order;

/**
 * Class PaymentContext
 * @package AdyenPayment\Components\Payload
 */
class PaymentContext
{
    /**
     * @var array
     */
    private $paymentInfo;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var sBasket
     */
    private $basket;

    /**
     * @var array
     */
    private $browserInfo;

    /**
     * @var array
     */
    private $shopperInfo;

    /**
     * @var string
     */
    private $origin;

    /**
     * @var PaymentInfo
     */
    private $transaction;

    /**
     * @var bool
     */
    private $storePaymentMethod;

    /**
     * PaymentContext constructor.
     * @param array $paymentInfo
     * @param Order $order
     * @param sBasket $basket
     * @param array $browserInfo
     * @param array $shopperInfo
     * @param string $origin
     * @param PaymentInfo $transaction
     * @param bool $storePaymentMethod
     */
    public function __construct(
        array $paymentInfo,
        Order $order,
        sBasket $basket,
        array $browserInfo,
        array $shopperInfo,
        string $origin,
        PaymentInfo $transaction,
        bool $storePaymentMethod
    ) {
        $this->paymentInfo = $paymentInfo;
        $this->order = $order;
        $this->basket = $basket;
        $this->browserInfo = $browserInfo;
        $this->shopperInfo = $shopperInfo;
        $this->origin = $origin;
        $this->transaction = $transaction;
        $this->storePaymentMethod = $storePaymentMethod;
    }

    public function getPaymentInfo(): array
    {
        return $this->paymentInfo;
    }

    public function getOrder(): Order
    {
        return $this->order;
    }

    public function getBasket(): sBasket
    {
        return $this->basket;
    }

    public function getBrowserInfo(): array
    {
        return $this->browserInfo;
    }

    public function getShopperInfo(): array
    {
        return $this->shopperInfo;
    }

    public function getOrigin(): string
    {
        return $this->origin;
    }

    public function getTransaction(): PaymentInfo
    {
        return $this->transaction;
    }

    public function enableStorePaymentMethod(): bool
    {
        return $this->storePaymentMethod;
    }
}
