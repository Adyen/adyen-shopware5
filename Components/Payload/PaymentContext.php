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
     * PaymentContext constructor.
     * @param array $paymentInfo
     * @param Order $order
     * @param sBasket $basket
     * @param array $browserInfo
     * @param array $shopperInfo
     * @param string $origin
     */
    public function __construct(
        array $paymentInfo,
        Order $order,
        sBasket $basket,
        array $browserInfo,
        array $shopperInfo,
        string $origin
    ) {
        $this->paymentInfo = $paymentInfo;
        $this->order = $order;
        $this->basket = $basket;
        $this->browserInfo = $browserInfo;
        $this->shopperInfo = $shopperInfo;
        $this->origin = $origin;
    }

    /**
     * @return array
     */
    public function getPaymentInfo(): array
    {
        return $this->paymentInfo;
    }

    /**
     * @return Order
     */
    public function getOrder(): Order
    {
        return $this->order;
    }

    /**
     * @return sBasket
     */
    public function getBasket(): sBasket
    {
        return $this->basket;
    }

    /**
     * @return array
     */
    public function getBrowserInfo(): array
    {
        return $this->browserInfo;
    }

    /**
     * @return array
     */
    public function getShopperInfo(): array
    {
        return $this->shopperInfo;
    }

    /**
     * @return string
     */
    public function getOrigin(): string
    {
        return $this->origin;
    }
}
