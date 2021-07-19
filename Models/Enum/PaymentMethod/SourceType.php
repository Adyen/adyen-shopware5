<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Enum\PaymentMethod;

final class SourceType
{
    // @see Shopware/Models/Payment/Payment.php
    private static $DEFAULT_PAYMENT = null;
    private static $SELF_CREATED = 1;
    private static $ADYEN = 2;

    /**
     * @var int | null
     */
    private $type;

    private function __construct($sourceType)
    {
        if (!self::isTypeAllowed($sourceType)) {
            throw new \InvalidArgumentException('Invalid source type: "' . $sourceType . '"');
        }

        $this->type = $sourceType;
    }

    /**
     * @return int|null
     */
    public function getType()
    {
        return $this->type;
    }

    public function equals(SourceType $sourceType): bool
    {
        return $sourceType->getType() === $this->type;
    }

    public static function load($sourceType): self
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

    public static function isTypeAllowed($sourceType): bool
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
            self::$DEFAULT_PAYMENT,
            self::$SELF_CREATED,
            self::$ADYEN
        ];
    }
}
