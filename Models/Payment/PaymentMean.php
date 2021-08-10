<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;

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

    /**
     * @return mixed|null
     */
    public function getValue(string $key, $fallback)
    {
        return $this->raw[$key] ?? $fallback;
    }

    public function getRaw(): array
    {
        return $this->raw;
    }
}
