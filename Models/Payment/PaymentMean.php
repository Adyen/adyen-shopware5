<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;

final class PaymentMean
{
    private int $id;
    private SourceType $source;
    private array $raw;
    private bool $enriched;

    public static function createFromShopwareArray(array $paymentMean): self
    {
        $new = new self();
        $new->id = (int) ($paymentMean['id'] ?? 0);
        $new->source = SourceType::load((int) $paymentMean['source']);
        $new->raw = $paymentMean;
        $new->enriched = (bool) ($paymentMean['enriched'] ?? false);

        return $new;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getSource(): SourceType
    {
        return $this->source;
    }

    public function getAttribute(): Attribute
    {
        return $this->raw['attribute'] ?? new Attribute();
    }

    public function isEnriched(): bool
    {
        return $this->enriched;
    }

    public function getAdyenUniqueIdentifier(): string
    {
        if ($this->getAttribute()->exists(AdyenPayment::ADYEN_UNIQUE_IDENTIFIER)) {
            return (string) $this->getAttribute()->get(AdyenPayment::ADYEN_UNIQUE_IDENTIFIER);
        }

        return '';
    }

    public function getAdyenStoredMethodId(): string
    {
        if ($this->getAttribute()->exists(AdyenPayment::ADYEN_STORED_METHOD_ID)) {
            return (string) $this->getAttribute()->get(AdyenPayment::ADYEN_STORED_METHOD_ID);
        }

        return '';
    }

    /**
     * @param mixed|null $fallback
     *
     * @return mixed|null
     */
    public function getValue(string $key, $fallback = null)
    {
        return $this->raw[$key] ?? $fallback;
    }

    public function getRaw(): array
    {
        return $this->raw;
    }

    public function isAdyenSourceType(): bool
    {
        return $this->source->equals(SourceType::adyen());
    }
}
