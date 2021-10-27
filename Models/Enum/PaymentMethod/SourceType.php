<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Enum\PaymentMethod;

final class SourceType
{
    private const DEFAULT_PAYMENT = null; // Shopware default payment mean source
    private const SELF_CREATED = 1; // User created payment mean source
    private const ADYEN = 1425514; // Adyen specific payment mean, avoid conflict with other plugins

    private ?int $type;

    private function __construct(?int $sourceType)
    {
        $this->type = $sourceType;
    }

    public function getType(): ?int
    {
        return $this->type;
    }

    public function equals(SourceType $sourceType): bool
    {
        return $sourceType->getType() === $this->type;
    }

    public static function load(?int $sourceType): self
    {
        return new self($sourceType);
    }

    public static function shopwareDefault(): self
    {
        return new self(self::DEFAULT_PAYMENT);
    }

    public static function shopwareSelfCreated(): self
    {
        return new self(self::SELF_CREATED);
    }

    public static function adyen(): self
    {
        return new self(self::ADYEN);
    }
}
