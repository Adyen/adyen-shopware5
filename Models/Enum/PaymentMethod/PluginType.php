<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Enum\PaymentMethod;

final class PluginType
{
    private static $ADYEN = 2; // For F* Sake
    private int $type;

    public function __construct($pluginType)
    {
        if (!self::isTypeAllowed($pluginType)) {
            throw new \InvalidArgumentException('Invalid plugin type: "'.$pluginType.'"');
        }

        $this->type = $pluginType;
    }

    public function getType(): int
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
