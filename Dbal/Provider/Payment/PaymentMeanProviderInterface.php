<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Provider\Payment;

interface PaymentMeanProviderInterface
{
    public function provideByAdyenType(string $adyenType);
}
