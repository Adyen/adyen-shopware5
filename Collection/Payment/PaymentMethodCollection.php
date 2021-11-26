<?php

declare(strict_types=1);

namespace AdyenPayment\Collection\Payment;

use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\Payment\PaymentMethodType;

final class PaymentMethodCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var array<PaymentMethod>
     */
    private array $paymentMethods;

    public function __construct(PaymentMethod ...$paymentMethods)
    {
        $this->paymentMethods = $paymentMethods;
    }

    /**
     * @return \Generator<PaymentMethod>
     */
    public function getIterator(): \Traversable
    {
        yield from $this->paymentMethods;
    }

    public function count(): int
    {
        return count($this->paymentMethods);
    }

    public static function fromAdyenMethods(array $adyenMethods): self
    {
        return new self(
            ...array_map(
                static fn(array $paymentMethod) => PaymentMethod::fromRaw($paymentMethod),
                $adyenMethods['paymentMethods'] ?? []
            )
        );
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->paymentMethods);
    }

    public function mapToRaw(): array
    {
        return array_map(
            static fn(PaymentMethod $paymentMethod) => $paymentMethod->getRawData(),
            $this->paymentMethods
        );
    }

    /**
     * $paymentTypeOrId is the Adyen "type" or Adyen "stored payment id"
     * NOT the Shopware id
     */
    public function fetchByTypeOrId(string $paymentTypeOrId): ?PaymentMethod
    {
        foreach ($this->paymentMethods as $paymentMethod) {
            if ($paymentMethod->getStoredPaymentMethodId() === $paymentTypeOrId) {
                return $paymentMethod;
            }

            if ($paymentMethod->getType() === $paymentTypeOrId) {
                return $paymentMethod;
            }
        }

        return null;
    }

    public function fetchByPaymentMean(PaymentMean $paymentMean): ?PaymentMethod
    {
        if ('' === $paymentMean->getAdyenStoredMethodId() && '' === $paymentMean->getAdyenType()) {
            return null;
        }

        if ($paymentMean->getAdyenStoredMethodId()) {
            return $this->fetchByTypeOrId($paymentMean->getAdyenStoredMethodId());
        }

        return $this->fetchByTypeOrId($paymentMean->getAdyenType());
    }

    public function filter(callable $filter): self
    {
        return new self(...array_filter($this->paymentMethods, $filter));
    }

    public function filterByPaymentType(PaymentMethodType $paymentMethodType): self
    {
        return new self(...array_filter(
            $this->paymentMethods,
            static fn(PaymentMethod $paymentMethod) => $paymentMethod->getPaymentMethodType()
                ->equals($paymentMethodType)
        ));
    }
}
