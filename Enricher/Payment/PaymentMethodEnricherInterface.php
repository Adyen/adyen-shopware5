<?php

declare(strict_types=1);

namespace AdyenPayment\Enricher\Payment;

use AdyenPayment\Models\Payment\PaymentMethod;

interface PaymentMethodEnricherInterface
{
    /**
     * @return array enriched $shopwareMethod
     */
    public function enrichPaymentMethod(array $shopwareMethod, PaymentMethod $paymentMethod): array;
    public function enrichStoredPaymentMethod(PaymentMethod $paymentMethod): array;
}
