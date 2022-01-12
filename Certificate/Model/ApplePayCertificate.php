<?php

declare(strict_types=1);

namespace AdyenPayment\Certificate\Model;

class ApplePayCertificate
{
    private string $certificate;

    private function __construct(string $certificate)
    {
        $this->certificate = $certificate;
    }

    public static function create(string $certificate): self
    {
        return new self($certificate);
    }

    public function certificate(): string
    {
        return $this->certificate;
    }
}
