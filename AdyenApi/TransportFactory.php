<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi;

use Adyen\Service\Checkout;
use Adyen\Service\Recurring;
use AdyenPayment\AdyenApi\HttpClient\ClientFactoryInterface;
use Shopware\Models\Shop\Shop;

final class TransportFactory implements TransportFactoryInterface
{
    /** @var ClientFactoryInterface */
    private $apiFactory;

    public function __construct(ClientFactoryInterface $apiFactory)
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
