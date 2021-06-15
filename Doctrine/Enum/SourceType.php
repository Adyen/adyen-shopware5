<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Enum;

/**
 * Usage:
 *  construct:          $sourceType = SourceType::adyenType()
 *  validate equality:  $sourceType->equals(SourceType::adyenType())
 *  get string value:  $sourceType->getType().
 *
 * only use ::load() when fetching data
 */
final class SourceType
{
    /**
     * As defined in Shopware/Models/Payment/Payment.php
     * NULL = default payment, 1 = self-created
     */
    private static $DEFAULT_TYPE = null;
    private static $SELF_CREATED = 1;
    private static $ADYEN = 2;

    /**
     * @var int|null
     */
    private $type;

    /**
     * SourceType constructor.
     * @param $sourceType
     */
    public function __construct($sourceType)
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

    public static function load(int $sourceType): self
    {
        return new self($sourceType);
    }

    public static function defaultType(): self
    {
        return new self(self::$DEFAULT_TYPE);
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
            self::$DEFAULT_TYPE,
            self::$SELF_CREATED,
            self::$ADYEN
        ];
    }
}