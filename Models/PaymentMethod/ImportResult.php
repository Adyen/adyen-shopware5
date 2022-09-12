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

    /** @var PaymentMethod|null */
    private $paymentMethod;

    /** @var bool */
    private $success;

    /** @var \Exception|null */
    private $exception;

    /** @var ImportStatus */
    private $status;

    public static function success(Shop $shop, PaymentMethod $paymentMethod, ImportStatus $importStatus): self
    {
        $new = new self();
        $new->shop = $shop;
        $new->paymentMethod = $paymentMethod;
        $new->success = true;
        $new->exception = null;
        $new->status = $importStatus;

        return $new;
    }

    public static function successSubShopFallback(Shop $shop, ImportStatus $importStatus): self
    {
        $new = new self();
        $new->shop = $shop;
        $new->paymentMethod = null;
        $new->success = true;
        $new->exception = null;
        $new->status = $importStatus;

        return $new;
    }

    public static function fromException(Shop $shop, ?PaymentMethod $paymentMethod, \Exception $exception): self
    {
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

    public function getPaymentMethod(): ?PaymentMethod
    {
        return $this->paymentMethod;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function getException(): ?\Exception
    {
        return $this->exception;
    }

    public function getStatus(): ImportStatus
    {
        return $this->status;
    }
}
