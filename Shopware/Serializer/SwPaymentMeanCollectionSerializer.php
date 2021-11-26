<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Serializer;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;
use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Serializer\PaymentMeanCollectionSerializer;
use AdyenPayment\Serializer\PaymentMeanSerializer;

final class SwPaymentMeanCollectionSerializer implements PaymentMeanCollectionSerializer
{
    private PaymentMeanSerializer $paymentMeanSerializer;

    public function __construct(PaymentMeanSerializer $paymentMeanSerializer)
    {
        $this->paymentMeanSerializer = $paymentMeanSerializer;
    }

    public function __invoke(PaymentMeanCollection $paymentMeans): array
    {
        return array_reduce(
            iterator_to_array($paymentMeans->getIterator()),
            fn (array $carry, PaymentMean $paymentMean) => [
                ...$carry,
                ...($this->paymentMeanSerializer)($paymentMean),
            ],
            []
        );
    }
}
