<?php

declare(strict_types=1);

namespace AdyenPayment\Models\PaymentMethod;

use AdyenPayment\Models\Enum\PaymentMethod\ImportStatus;
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
    /** @var null | \Exception */
    private $exception = null;
    /** @var ImportStatus */
    private $status;

    public static function success(
        Shop $shop,
        PaymentMethod $paymentMethod,
        ImportStatus $importStatus
    ): ImportResult {
        $new = new self();
        $new->shop = $shop;
        $new->paymentMethod = $paymentMethod;
        $new->success = true;
        $new->status = $importStatus;

        return $new;
    }

    public static function successSubshopFallback(
        Shop $shop,
        ImportStatus $importStatus
    ): ImportResult {
        $new = new self();
        $new->shop = $shop;
        $new->success = true;
        $new->status = $importStatus;

        return $new;
    }

    /**
     * @param PaymentMethod | null $paymentMethod
     */
    public static function fromException(
        Shop $shop,
        $paymentMethod,
        \Exception $exception
    ): ImportResult {
        $new = new self();
        $new->shop = $shop;
        $new->paymentMethod = $paymentMethod;
        $new->success = false;
        $new->exception = $exception;
        $new->status = ImportStatus::notHandledStatus();

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

    public function getStatus(): ImportStatus
    {
        return $this->status;
    }
}
