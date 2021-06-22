<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Enum;

use http\Exception\InvalidArgumentException;

final class PluginType
{
    private static $ADYEN = 2;

    /** @var int */
    private $type;

    public function __construct($pluginType)
    {
        if (!self::isTypeAllowed($pluginType)) {
            throw new InvalidArgumentException('Invalid plugin type: "' . $pluginType . '"');
        }

        $this->type = $pluginType;
    }

    /**
     * @return int
     */
    public function getType()
    {
        return $this->type;
    }

    public function equals(PluginType $pluginType): bool
    {
        return $pluginType->getType() === $this->type;
    }

    public static function load(int $pluginType): self
    {
        return new self($pluginType);
    }

    public static function adyenType(): self
    {
        return new self(self::$ADYEN);
    }

    public static function isTypeAllowed(int $pluginType): bool
    {
        return in_array($pluginType, self::availableTypes(), true);
    }

    /**
     * @internal
     *
     * @return string[]
     */
    public static function availableTypes(): array
    {
        return [
            self::$ADYEN,
        ];
    }
}