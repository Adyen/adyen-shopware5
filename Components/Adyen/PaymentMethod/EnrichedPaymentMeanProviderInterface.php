<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\Collection\Payment\PaymentMeanCollection;

interface EnrichedPaymentMeanProviderInterface
{
    public function __invoke(PaymentMeanCollection $paymentMeans): PaymentMeanCollection;
}
