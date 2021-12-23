<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\PaymentMethod;

use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use Shopware\Models\Shop\Shop;

interface PaymentMethodsProviderInterface
{
    public function __invoke(Shop $shop): PaymentMethodCollection;
}
