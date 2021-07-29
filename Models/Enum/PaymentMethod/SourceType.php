<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Enum\PaymentMethod;

/**
 * @see \Shopware\Models\Payment\Payment::$source
 */
final class SourceType
{
    /**
     * @see \Shopware\Models\Payment\Payment::$source
     */
    private static $DEFAULT_PAYMENT = null; // Shopware default payment mean source
    private static $SELF_CREATED = 1; // User created payment mean source
    private static $ADYEN = 1425514; // Adyen specific payment mean, avoid conflict with other plugins

    /**
     * @var int | null
     */
    private $type;

    /**
     * @param int|null $sourceType
     */
    private function __construct($sourceType)
    {
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

    public static function shopwareDefault(): self
    {
        return new self(self::$DEFAULT_PAYMENT);
    }

    public static function shopwareSelfCreated(): self
    {
        return new self(self::$SELF_CREATED);
    }

    public static function adyen(): self
    {
        return new self(self::$ADYEN);
    }
}
