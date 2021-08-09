<?php

declare(strict_types=1);

namespace AdyenPayment\Collection\Payment;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMean;
use Countable;
use iterable;
use IteratorAggregate;

final class PaymentMeanCollection implements IteratorAggregate, Countable
{
    /**
     * @var array<PaymentMean>
     */
    private $paymentMeans;

    public function __construct(PaymentMean ...$paymentMeans)
    {
        $this->paymentMeans = $paymentMeans;
    }

    public static function createFromShopwareArray(array $paymentMeans): self
    {
        return new self(
            ...array_map(function(array $paymentMean) {
                return PaymentMean::createFromShopwareArray($paymentMean);
            },
            $paymentMeans
        ));
    }

    /**
     * @return iterable<PaymentMean>
     */
    public function getIterator(): iterable
    {
        yield from $this->paymentMeans;
    }

    public function count(): int
    {
        return \count($this->paymentMeans);
    }

    public function map(callable $callable): array
    {
        return array_map($callable, $this->paymentMeans);
    }

    public function filter(callable $filter = null): self
    {
        return new self(...array_values(array_filter($this->paymentMeans, $filter)));
    }

    public function filterBySource(SourceType $source): self
    {
        return $this->filter(
            static function (PaymentMean $paymentMean) use ($source) {
                return $source->equals($paymentMean->getSource());
            }
        );
    }

    public function filterByAdyenSource(): self
    {
        return $this->filterBySource(SourceType::adyen());
    }

    public function toShopwareArray(): array
    {
        return $this->map(
            static function (PaymentMean $paymentMean) {
                return $paymentMean->getRaw();
            }
        );
    }

}
