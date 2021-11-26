<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;

final class PaymentMean
{
    /** @var int|null */
    private $id;

    /** @var SourceType */
    private $source;

    /** @var array */
    private $raw;

    public static function createFromShopwareArray(array $paymentMean): self
    {
        $new = new self();
        $new->id = (int) ($paymentMean['id'] ?? 0);
        $new->source = SourceType::load((int) $paymentMean['source']);
        $new->raw = $paymentMean;

        return $new;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return SourceType
     */
    public function getSource()
    {
        return $this->source;
    }

    public function getAttribute(): Attribute
    {
        return $this->raw['attribute'] ?? new Attribute();
    }

    public function getAdyenType(): string
    {
        if ($this->getAttribute()->exists(AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL)) {
            return (string) $this->getAttribute()->get(AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL);
        }

        return '';
    }

    public function getAdyenStoredMethodId(): string
    {
        if ($this->getAttribute()->exists(AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID)) {
            return (string) $this->getAttribute()->get(AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID);
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

    public function isAdyenType(): bool
    {
        return $this->source->equals(SourceType::adyen());
    }
}
