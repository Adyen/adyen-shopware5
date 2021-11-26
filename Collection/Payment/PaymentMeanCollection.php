<?php

declare(strict_types=1);

namespace AdyenPayment\Collection\Payment;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Enricher\Payment\PaymentMethodEnricherInterface;
use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use AdyenPayment\Models\Payment\PaymentMean;
use Countable;
use IteratorAggregate;
use Shopware\Bundle\StoreFrontBundle\Struct\Attribute;

final class PaymentMeanCollection implements IteratorAggregate, Countable
{
    /**
     * @var array<PaymentMean>
     */
    private array $paymentMeans;

    public function __construct(PaymentMean ...$paymentMeans)
    {
        $this->paymentMeans = $paymentMeans;
    }

    public static function createFromShopwareArray(array $paymentMeans): self
    {
        return new self(
            ...array_map(
                static fn(array $paymentMean) => PaymentMean::createFromShopwareArray($paymentMean),
                $paymentMeans
            )
        );
    }

    /**
     * @return \Generator<PaymentMean>
     */
    public function getIterator(): \Traversable
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

    public function filter(callable $filter): self
    {
        return new self(...array_filter($this->paymentMeans, $filter));
    }

    public function filterBySource(SourceType $source): self
    {
        return $this->filter(
            static function(PaymentMean $paymentMean) use ($source) {
                return $source->equals($paymentMean->getSource());
            }
        );
    }

    public function filterExcludeAdyen(): self
    {
        return $this->filter(
            static function(PaymentMean $paymentMean) {
                return !$paymentMean->getSource()->equals(SourceType::adyen());
            }
        );
    }

    public function filterByAdyenSource(): self
    {
        return $this->filterBySource(SourceType::adyen());
    }

    public function toShopwareArray(): array
    {
        return array_reduce(
            $this->paymentMeans,
            static function(array $payload, PaymentMean $paymentMean) {
                $payload[$paymentMean->getId()] = $paymentMean->getRaw();

                return $payload;
            },
            []
        );
    }

    public function enrichAdyenPaymentMeans(
        PaymentMethodCollection $adyenPaymentMethods,
        PaymentMethodEnricherInterface $paymentMethodEnricher
    ): self {
        return new self(...array_filter($this->map(
            static function(PaymentMean $shopwareMethod) use (
                $adyenPaymentMethods,
                $paymentMethodEnricher
            ) {
                if (!$shopwareMethod->getSource()->equals(SourceType::adyen())) {
                    return $shopwareMethod;
                }

                /** @var Attribute $attribute */
                $attribute = $shopwareMethod->getValue('attribute');
                $typeOrId = $attribute->get(AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID)
                    ?: $attribute->get(AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL);

                $paymentMethod = $adyenPaymentMethods->fetchByTypeOrId($typeOrId);
                if (null === $paymentMethod) {
                    return null;
                }

                return PaymentMean::createFromShopwareArray(
                    $paymentMethodEnricher->enrichPaymentMethod($shopwareMethod->getRaw(), $paymentMethod)
                );
            }
        )));
    }
}
