<?php

declare(strict_types=1);

namespace AdyenPayment\Applepay\MerchantAssociation\Model;

final class InstallResult
{
    /** @var bool */
    private $fallback;

    /** @var bool */
    private $success;

    /** @var \Exception|null */
    private $exception;

    private function __construct(bool $success)
    {
        $this->success = $success;
        $this->fallback = false;
        $this->exception = null;
    }

    public static function fromSuccess(): self
    {
        return new self(true);
    }

    public static function fromException(\Exception $exception): self
    {
        $new = new self(false);
        $new->exception = $exception;

        return $new;
    }

    public function withFallback(bool $fallback): self
    {
        $new = clone $this;
        $new->fallback = $fallback;

        return $new;
    }

    public function fallback(): bool
    {
        return $this->fallback;
    }

    public function success(): bool
    {
        return $this->success;
    }

    public function exception(): ?\Exception
    {
        return $this->exception;
    }
}
