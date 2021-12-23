<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

use Adyen\Client;
use Shopware\Models\Shop\Shop;

class ApiClientMap
{
    /**
     * @var array<int|string, Client>
     */
    private $memoisedClients = [];

    /**
     * @var ApiFactory
     */
    private $factory;

    public function __construct(ApiFactory $factory)
    {
        $this->factory = $factory;
    }

    public function lookup(Shop $shop): Client
    {
        if (!array_key_exists($shop->getId(), $this->memoisedClients)) {
            $this->memoisedClients[$shop->getId()] = $this->factory->provide($shop);
        }

        return $this->memoisedClients[$shop->getId()];
    }
}
