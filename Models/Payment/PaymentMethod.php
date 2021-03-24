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
     * @var array raw data
     */
    private $rawData;

    private function __construct(PaymentMethodType $type, array $rawData)
    {
        $this->paymentMethodType = $type;
        $this->rawData = $rawData;
    }

    public static function fromRawPaymentData(array $data): self
    {
        return new self(
            array_key_exists('id', $data) ? PaymentMethodType::stored() : PaymentMethodType::default(),
            $data
        );
    }

    public function getPaymentMethodType(): PaymentMethodType
    {
        return $this->paymentMethodType;
    }

    public function isStoredPayment(): bool
    {
        return $this->getPaymentMethodType()->equals(PaymentMethodType::stored());
    }

    public function equalsDefaultPaymentType(string $type): bool
    {
        return $this->getPaymentMethodType()->equals(PaymentMethodType::default())
            && $this->getType() === $type;
    }

    public function equalsStoredPaymentId(string $id): bool
    {
        return $this->getPaymentMethodType()->equals(PaymentMethodType::stored())
            && $this->getId() === $id;
    }

    public function getId(): string
    {
        return $this->rawData['id'] ?? '';
    }

    public function getType(): string
    {
        return $this->rawData['type'] ?? '';
    }

    public function getRawData(): array
    {
        return $this->rawData;
    }

    public function serializeMinimalState(): string
    {
        return json_encode([
            'type' => $this->getType(),
        ]);
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
}
