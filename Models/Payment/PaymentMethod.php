<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

use AdyenPayment\Utils\Sanitize;

final class PaymentMethod
{
    private PaymentGroup $group;
    private PaymentType $type;

    /**
     * @var array<string,mixed>
     */
    private array $rawData;

    private function __construct()
    {
    }

    public static function fromRaw(array $data): self
    {
        $new = new self();
        $new->group = array_key_exists('id', $data) ? PaymentGroup::stored() : PaymentGroup::default();
        $new->type = PaymentType::load((string) ($data['type'] ?? ''));
        $new->rawData = $data;

        return $new;
    }

    public function uniqueIdentifier(): string
    {
        return mb_strtolower(sprintf('%s_%s',
            $this->adyenType()->type(),
            Sanitize::removeNonWord($this->name())
        ));
    }

    public function adyenType(): PaymentType
    {
        return $this->type;
    }

    public function group(): PaymentGroup
    {
        return $this->group;
    }

    public function rawData(): array
    {
        return $this->rawData;
    }

    public function name(): string
    {
        return (string) ($this->rawData['name'] ?? '');
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

    public function isStoredPayment(): bool
    {
        return $this->group()->equals(PaymentGroup::stored());
    }

    public function hasDetails(): bool
    {
        return array_key_exists('details', $this->rawData) && 0 !== count((array) $this->rawData['details']);
    }

    public function serializeMinimalState(): string
    {
        return json_encode([
            'type' => $this->adyenType()->type(),
        ]);
    }
}
