<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

use AdyenPayment\Utils\Sanitize;

final class PaymentMethod
{
    private PaymentMethodType $paymentMethodType;

    /**
     * @var array<string,mixed>
     */
    private array $rawData;

    private function __construct(PaymentMethodType $type, array $rawData)
    {
        $this->paymentMethodType = $type;
        $this->rawData = $rawData;
    }

    public function getPaymentMethodType(): PaymentMethodType
    {
        return $this->paymentMethodType;
    }

    public function uniqueIdentifier(): string
    {
        return mb_strtolower(sprintf('%s_%s',
            $this->getType(),
            Sanitize::removeNonWord($this->getValue('name', ''))
        ));
    }

    /**
     * shortcut to get value of raw payment data.
     *
     * @return mixed|null
     *
     * @psalm-param ''|null $fallback
     */
    public function getValue(string $key, ?string $fallback = null)
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

    public function hasDetails(): bool
    {
        return array_key_exists('details', $this->rawData) && 0 !== count((array) $this->rawData['details']);
    }

    public function serializeMinimalState(): string
    {
        return json_encode([
            'type' => $this->getType(),
        ]);
    }
}
