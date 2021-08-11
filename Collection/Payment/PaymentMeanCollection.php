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
    private $paymentMeans;

    public function __construct(PaymentMean ...$paymentMeans)
    {
        $this->paymentMeans = $paymentMeans;
    }

    public static function createFromShopwareArray(array $paymentMeans): self
    {
        return new self(
            ...array_map(
                function (array $paymentMean) {
                    return PaymentMean::createFromShopwareArray($paymentMean);
                },
                $paymentMeans
            )
        );
    }

    /**
     * @return \Generator<PaymentMean>
     */
    public function getIterator(): \Generator
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
        return array_reduce(
            $this->paymentMeans,
            static function (array $payload, PaymentMean $paymentMean) {
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
            static function (PaymentMean $shopwareMethod) use (
                $adyenPaymentMethods,
                $paymentMethodEnricher
            ) {
                $source = $shopwareMethod->getSource();
                if (!SourceType::load($source->getType())->equals(SourceType::adyen())) {
                    return $shopwareMethod;
                }

                /** @var Attribute $attribute */
                $attribute = $shopwareMethod->getRaw()['attribute'];
                $typeOrId = $attribute->get(AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID)
                    ?: $attribute->get(AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL);

                $paymentMethod = $adyenPaymentMethods->fetchByTypeOrId($typeOrId);

                return null === $paymentMethod
                    ? null
                    : PaymentMean::createFromShopwareArray(
                        $paymentMethodEnricher->enrichPaymentMethod($shopwareMethod->getRaw(), $paymentMethod)
                    );
            }
        )));
    }
}
