<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\HttpClient;

use Adyen\Client;
use Shopware\Models\Shop\Shop;

final class ClientMemoise implements ClientMemoiseInterface
{
    /**
     * @var array<int|string, Client>
     */
    private $memoisedClients = [];
    private ClientFactoryInterface $factory;

    public function __construct(ClientFactoryInterface $factory)
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
