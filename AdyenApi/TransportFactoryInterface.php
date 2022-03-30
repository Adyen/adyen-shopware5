<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi;

use Adyen\Service\Checkout;
use Adyen\Service\Recurring;
use Shopware\Models\Shop\Shop;

interface TransportFactoryInterface
{
    public function recurring(Shop $shop): Recurring;
    public function checkout(Shop $shop): Checkout;
}
