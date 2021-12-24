<?php

declare(strict_types=1);

namespace AdyenPayment\Collection\Payment;

use AdyenPayment\Models\Payment\PaymentGroup;
use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Models\Payment\PaymentMethod;

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
            ),
            ...array_map(
                static fn(array $paymentMethod) => PaymentMethod::fromRaw($paymentMethod),
                $adyenMethods['storedPaymentMethods'] ?? []
            )
        );
    }

    public function withImportLocale(PaymentMethodCollection $importLocalePaymentMethods): self
    {
        $importPaymentMethods = iterator_to_array($importLocalePaymentMethods);

        return new self(...array_map(
            static function(int $index, PaymentMethod $paymentMethod) use ($importPaymentMethods): PaymentMethod {
                /** @var PaymentMethod $importMethod */
                $importLocaleMethod = $importPaymentMethods[$index] ?? null;
                if (!$importLocaleMethod) {
                    return $paymentMethod;
                }

                return $paymentMethod->withCode($importLocaleMethod->name());
            },
            array_keys($this->paymentMethods),
            array_values($this->paymentMethods)
        ));
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->paymentMethods);
    }

    public function mapToRaw(): array
    {
        return array_map(
            static fn(PaymentMethod $paymentMethod) => $paymentMethod->rawData(),
            $this->paymentMethods
        );
    }

    /**
     * $identifierOrStoredId is the Adyen "unique identifier" or Adyen "stored payment id"
     * NOT the Shopware id.
     */
    public function fetchByIdentifierOrStoredId(string $identifierOrStoredId): ?PaymentMethod
    {
        foreach ($this->paymentMethods as $paymentMethod) {
            if ($paymentMethod->getStoredPaymentMethodId() === $identifierOrStoredId) {
                return $paymentMethod;
            }

            if ($paymentMethod->code() === $identifierOrStoredId) {
                return $paymentMethod;
            }
        }

        return null;
    }

    public function fetchByPaymentMean(PaymentMean $paymentMean): ?PaymentMethod
    {
        if ('' === $paymentMean->getAdyenStoredMethodId() && '' === $paymentMean->getAdyenCode()) {
            return null;
        }

        if ($paymentMean->getAdyenStoredMethodId()) {
            return $this->fetchByIdentifierOrStoredId($paymentMean->getAdyenStoredMethodId());
        }

        return $this->fetchByIdentifierOrStoredId($paymentMean->getAdyenCode());
    }

    public function filter(callable $filter): self
    {
        return new self(...array_filter($this->paymentMethods, $filter));
    }

    public function filterByPaymentType(PaymentGroup $group): self
    {
        return new self(...array_filter(
            $this->paymentMethods,
            static fn(PaymentMethod $paymentMethod) => $paymentMethod->group()->equals($group)
        ));
    }
}
