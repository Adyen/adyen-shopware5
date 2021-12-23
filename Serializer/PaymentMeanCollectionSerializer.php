<?php

declare(strict_types=1);

namespace AdyenPayment\Serializer;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;

interface PaymentMeanCollectionSerializer
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function __invoke(PaymentMeanCollection $paymentMeans): array;
}
