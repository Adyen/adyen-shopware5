<?php

declare(strict_types=1);

namespace AdyenPayment\Serializer;

use AdyenPayment\Models\Payment\PaymentMean;

interface PaymentMeanSerializer
{
    /**
     * @return array<string, mixed>
     */
    public function __invoke(PaymentMean $paymentMean): array;
}
