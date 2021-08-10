<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

class PaymentMethod
{
    /**
     * @var PaymentMethodType
     */
    private $paymentMethodType;
    /**
     * @var array
     */
    private $rawData;

    private function __construct(PaymentMethodType $type, array $rawData)
    {
        $this->paymentMethodType = $type;
        $this->rawData = $rawData;
    }

    public function getPaymentMethodType(): PaymentMethodType
    {
        return $this->paymentMethodType;
    }

    /**
     * shortcut to get value of raw payment data
     * @param mixed|null  $fallback
     * @return mixed|null
     */
    public function getValue(string $key, $fallback = null)
    {
        return $this->rawData[$key] ?? $fallback;
    }

    public function getStoredPaymentMethodId(): string
    {
        return (string) ($this->rawData['id'] ?? '');
    }

    public function getId(): string
    {
        return (string) ($this->rawData['id'] ?? '');
    }

    public function getType(): string
    {
        return (string) ($this->rawData['type'] ?? '');
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public static function fromRaw(array $data): self
    {
        return new self(
            array_key_exists('id', $data) ? PaymentMethodType::stored() : PaymentMethodType::default(),
            $data
        );
    }

    public function isStoredPayment(): bool
    {
        return $this->getPaymentMethodType()->equals(PaymentMethodType::stored());
    }

    public function serializeMinimalState(): string
    {
        return json_encode([
            'type' => $this->getType(),
        ]);
    }
}
