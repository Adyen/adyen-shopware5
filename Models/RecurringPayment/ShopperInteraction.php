<?php

declare(strict_types=1);

namespace AdyenPayment\Models\RecurringPayment;

final class ShopperInteraction
{
    private const CONTAUTH = 'ContAuth';
    private const ECOMMERCE = 'Ecommerce';
    private string $value;

    private function __construct(string $shopperInteraction)
    {
        if (!in_array($shopperInteraction, $this->availableShopperInteractions(), true)) {
            throw new \InvalidArgumentException('Invalid shopper interaction: "'.$shopperInteraction.'"');
        }

        $this->value = $shopperInteraction;
    }

    public function value(): string
    {
        return $this->value;
    }

    public function equals(ShopperInteraction $paymentShopperInteraction): bool
    {
        return $paymentShopperInteraction->value() === $this->value;
    }

    public static function load(string $shopperInteraction): self
    {
        return new self($shopperInteraction);
    }

    private function availableShopperInteractions(): array
    {
        return [
            self::CONTAUTH,
            self::ECOMMERCE,
        ];
    }

    public static function contAuth(): self
    {
        return new self(self::CONTAUTH);
    }

    public static function ecommerce(): self
    {
        return new self(self::ECOMMERCE);
    }
}
