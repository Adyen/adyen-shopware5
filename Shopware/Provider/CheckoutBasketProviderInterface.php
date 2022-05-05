<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Provider;

interface CheckoutBasketProviderInterface
{
    public function __invoke($mergeProportional = true): array;
}
