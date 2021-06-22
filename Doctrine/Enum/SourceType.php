<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Enum;

final class SourceType
{
    // @see Shopware/Models/Payment/Payment.php
    private static $SELF_CREATED = 1;
    private static $ADYEN = 2;

    /**
     * @var int
     */
    private $type;

    public function __construct(int $sourceType)
    {
        if (!self::isTypeAllowed($sourceType)) {
            throw new \InvalidArgumentException('Invalid source type: "' . $sourceType . '"');
        }

        $this->type = $sourceType;
    }

    /**
     * @return int
     */
    public function getType(): int
    {
        return $this->type;
    }

    public function equals(SourceType $sourceType): bool
    {
        return $sourceType->getType() === $this->type;
    }

    public static function load(int $sourceType): self
    {
        return new self($sourceType);
    }

    public static function selfCreatedType(): self
    {
        return new self(self::$SELF_CREATED);
    }

    public static function adyenType(): self
    {
        return new self(self::$ADYEN);
    }

    public static function isTypeAllowed(int $sourceType): bool
    {
        return in_array($sourceType, self::availableTypes(), true);
    }

    /**
     * @internal
     *
     * @return string[]
     */
    public static function availableTypes(): array
    {
        return [
            self::$SELF_CREATED,
            self::$ADYEN
        ];
    }
}