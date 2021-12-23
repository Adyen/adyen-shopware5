<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

final class PaymentType
{
    private const GOOGLE_PAY = 'paywithgoogle';
    private const APPLE_PAY = 'applepay';
    private string $type;

    private function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function load(string $type): self
    {
        return new self($type);
    }

    public static function googlePay(): self
    {
        return new self(self::GOOGLE_PAY);
    }

    public static function applePay(): self
    {
        return new self(self::APPLE_PAY);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function equals(PaymentType $type): bool
    {
        return $this->type === $type->type();
    }
}
