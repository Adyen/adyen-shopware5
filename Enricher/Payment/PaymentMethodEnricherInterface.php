<?php

declare(strict_types=1);

namespace AdyenPayment\Enricher\Payment;

use AdyenPayment\Models\Payment\PaymentMethod;

interface PaymentMethodEnricherInterface
{
    public function __invoke(array $shopwareMethod, PaymentMethod $paymentMethod): array;
}
