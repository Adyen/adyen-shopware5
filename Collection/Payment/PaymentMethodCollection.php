<?php

declare(strict_types=1);

namespace AdyenPayment\Collection\Payment;

use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\Payment\PaymentMethodType;

final class PaymentMethodCollection implements \Countable, \IteratorAggregate
{
    /**
     * @var PaymentMethod[]
     */
    private $paymentMethods;

    public function __construct(PaymentMethod ...$paymentMethods)
    {
        $this->paymentMethods = $paymentMethods;
    }

    /**
     * @return \Generator<PaymentMethod>
     */
    public function getIterator(): \Generator
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
            ...array_map(function (array $paymentMethod) {
                return PaymentMethod::fromRaw($paymentMethod);
            }, $adyenMethods['paymentMethods'] ?? [])
        );
    }

    public function map(callable $callback): array
    {
        return array_map($callback, $this->paymentMethods);
    }

    public function mapToRaw(): array
    {
        return array_map(function (PaymentMethod $paymentMethod) {
            return $paymentMethod->getRawData();
        }, $this->paymentMethods);
    }

    /**
     * @return PaymentMethod|null
     */
    public function fetchByTypeOrId(string $paymentTypeOrId)
    {
        foreach ($this->paymentMethods as $paymentMethod) {
            if ($paymentMethod->getId() !== $paymentTypeOrId
                && $paymentMethod->getType() !== $paymentTypeOrId) {
                continue;
            }

            if ($paymentMethod->getPaymentMethodType()->equals(PaymentMethodType::stored())) {
                return $paymentMethod;
            }

            if ($paymentMethod->getPaymentMethodType()->equals(PaymentMethodType::default())) {
                return $paymentMethod;
            }
        }

        return null;
    }

    public function filter(callable $filter = null): self
    {
        return new self(...array_filter($this->paymentMethods, $filter));
    }

    public function filterByPaymentType(PaymentMethodType $paymentMethodType): self
    {
        return new self(...array_filter(
            $this->paymentMethods,
            function (PaymentMethod $paymentMethod) use ($paymentMethodType) {
                return $paymentMethod->getPaymentMethodType()->equals($paymentMethodType);
            }
        ));
    }
}
