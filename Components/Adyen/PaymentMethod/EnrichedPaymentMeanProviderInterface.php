<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

interface EnrichedPaymentMeanProviderInterface
{
    public function __invoke(array $shopwareMethods): array;
}
