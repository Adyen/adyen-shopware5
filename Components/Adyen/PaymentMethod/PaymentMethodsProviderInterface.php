<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use Shopware\Models\Shop\Shop;

interface PaymentMethodsProviderInterface
{
    public function __invoke(Shop $shop): array;
}
