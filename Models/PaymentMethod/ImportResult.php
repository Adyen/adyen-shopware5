<?php

declare(strict_types=1);

namespace AdyenPayment\Models\PaymentMethod;

use AdyenPayment\Models\Payment\PaymentMethod;
use Shopware\Models\Shop\Shop;

final class ImportResult
{
    /** @var Shop */
    private $shop;
    /** @var null | PaymentMethod */
    private $paymentMethod;
    /** @var bool */
    private $success;
    /** @var bool */
    private $updated;
    /** @var null | \Exception */
    private $exception = null;

    private function __construct()
    {
    }

    public static function success(
        Shop $shop,
        PaymentMethod $paymentMethod,
        bool $updated
    ) {
        $new = new self();
        $new->shop = $shop;
        $new->paymentMethod = $paymentMethod;
        $new->success = true;
        $new->updated = $updated;

        return $new;
    }

    /**
     * @param PaymentMethod | null $paymentMethod
     */
    public static function fromException(
        Shop $shop,
        $paymentMethod,
        \Exception $exception
    )
    {
        $new = new self();
        $new->shop = $shop;
        $new->paymentMethod = $paymentMethod;
        $new->success = false;
        $new->exception = $exception;

        return $new;
    }

    public function getShop(): Shop
    {
        return $this->shop;
    }

    /**
     * @return null|PaymentMethod
     */
    public function getPaymentMethod()
    {
        return $this->paymentMethod;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @return \Exception|null
     */
    public function getException(): \Exception
    {
        return $this->exception;
    }
}