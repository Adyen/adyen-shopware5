<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\HttpClient;

use Adyen\Client;
use Shopware\Models\Shop\Shop;

interface ClientFactoryInterface
{
    public function provide(Shop $shop): Client;
}
