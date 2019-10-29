<?php
declare(strict_types=1);

namespace MeteorAdyen\Components\Payload;

use sBasket;
use Shopware\Models\Order\Order;

/**
 * Class PaymentContext
 * @package MeteorAdyen\Components\Payload
 */
class PaymentContext
{
    private $paymentInfo;

    private $order;

    private $basket;

    private $browserInfo;

    private $shopperInfo;

    /**
     * @return array
     */
    public function getPaymentInfo(): array
    {
        return $this->paymentInfo;
    }

    /**
     * @param $paymentInfo
     * @return array
     */
    public function setPaymentInfo($paymentInfo): void
    {
        $this->paymentInfo = $paymentInfo;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @param $order
     * @return Order
     */
    public function setOrder($order): void
    {
        $this->order = $order;
    }

    /**
     * @return sBasket
     */
    public function getBasket(): sBasket
    {
        return $this->basket;
    }

    /**
     * @param $basket
     */
    public function setBasket($basket): void
    {
        $this->basket = $basket;
    }

    /**
     * @return array
     */
    public function getBrowserInfo(): array
    {
        return $this->browserInfo;
    }

    /**
     * @param $browserInfo
     */
    public function setBrowserInfo($browserInfo): void
    {
        $this->browserInfo = $browserInfo;
    }

    /**
     * @return array
     */
    public function getShopperInfo(): array
    {
        return $this->shopperInfo;
    }

    /**
     * @param array $shopperInfo
     */
    public function setShopperInfo($shopperInfo): void
    {
        $this->shopperInfo = $shopperInfo;
    }
}