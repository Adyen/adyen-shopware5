<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Provider;

interface PaymentMeansProviderInterface
{
    public function __invoke(): array;
}
