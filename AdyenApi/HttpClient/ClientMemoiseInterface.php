<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\HttpClient;

use Adyen\Client;
use Shopware\Models\Shop\Shop;

interface ClientMemoiseInterface
{
    public function lookup(Shop $shop): Client;
}
