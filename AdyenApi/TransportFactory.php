<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi;

use Adyen\Service\Checkout;
use Adyen\Service\Recurring;
use AdyenPayment\Components\Adyen\ApiFactory;
use Shopware\Models\Shop\Shop;

// TODO add a test
final class TransportFactory
{
    private ApiFactory $apiFactory;

    public function __construct(ApiFactory $apiFactory)
    {
        $this->apiFactory = $apiFactory;
    }

    public function recurring(Shop $shop): Recurring
    {
        return new Recurring(
            $this->apiFactory->provide($shop)
        );
    }

    public function checkout(Shop $shop): Checkout
    {
        return new Checkout(
            $this->apiFactory->provide($shop)
        );
    }
}
