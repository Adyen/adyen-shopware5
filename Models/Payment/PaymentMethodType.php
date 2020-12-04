<?php

declare(strict_types=1);

namespace AdyenPayment\Serializer\Payment;

final class PaymentMethodType
{
    private static $DEFAULT_METHOD_TYPE = 'payment';
    private static $STORED_METHOD_TYPE = 'stored';

    /**
     * @var string
     */
    private $type;

    private function __construct(string $paymentMethodType)
    {
        if (!in_array($paymentMethodType, $this->availableTypes())) {
            throw new \InvalidArgumentException('Invalid Payment method type: "'.$paymentMethodType.'"');
        }

        $this->type = $paymentMethodType;
    }

    public static function default(): self
    {
        return new self(self::$DEFAULT_METHOD_TYPE);
    }

    public static function stored(): self
    {
        return new self(self::$STORED_METHOD_TYPE);
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function equals(PaymentMethodType $type): bool
    {
        return $this->type === $type->getType();
    }

    /**
     * @return string[]
     */
    public function availableTypes(): array
    {
        return [
            self::$DEFAULT_METHOD_TYPE,
            self::$STORED_METHOD_TYPE,
        ];
    }
}
