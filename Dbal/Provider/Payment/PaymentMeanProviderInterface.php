<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Provider\Payment;

use Shopware\Models\Payment\Payment;

interface PaymentMeanProviderInterface
{
    public function provideByAdyenType(string $adyenType): ?Payment;
}
