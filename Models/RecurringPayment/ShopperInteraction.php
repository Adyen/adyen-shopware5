<?php

declare(strict_types=1);

namespace AdyenPayment\Models\RecurringPayment;

final class ShopperInteraction
{
    private const CONTAUTH = 'ContAuth';
    private const ECOMMERCE = 'Ecommerce';
    private const MOTO = 'Moto';
    private const POS = 'POS';
    private string $shopperInteraction;

    private function __construct(string $shopperInteraction)
    {
        if (!in_array($shopperInteraction, $this->availableShopperInteractions(), true)) {
            throw new \InvalidArgumentException('Invalid shopper interaction: "'.$shopperInteraction.'"');
        }

        $this->shopperInteraction = $shopperInteraction;
    }

    public function shopperInteraction(): string
    {
        return $this->shopperInteraction;
    }

    public function equals(ShopperInteraction $paymentShopperInteraction): bool
    {
        return $paymentShopperInteraction->shopperInteraction() === $this->shopperInteraction;
    }

    public static function load(string $shopperInteraction): self
    {
        return new self($shopperInteraction);
    }

    public static function contAuth(): self
    {
        return new self(self::CONTAUTH);
    }

    public static function ecommerce(): self
    {
        return new self(self::ECOMMERCE);
    }

    private function availableShopperInteractions(): array
    {
        return [
            self::CONTAUTH,
            self::ECOMMERCE,
            self::MOTO,
            self::POS,
        ];
    }
}
