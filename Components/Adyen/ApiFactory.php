<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Client;
use Adyen\Environment;
use AdyenPayment\Components\Configuration;
use Psr\Log\LoggerInterface;
use Shopware\Models\Shop\Shop;

/**
 * Class ApiFactory
 * @package AdyenPayment\Components\Adyen
 */
class ApiFactory
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var Client[]
     */
    private $apiClients = [];

    /**
     * ApiFactory constructor.
     * @param Configuration $configuration
     * @param LoggerInterface $logger
     */
    public function __construct(
        Configuration $configuration,
        LoggerInterface $logger
    ) {
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * @throws AdyenException
     */
    public function provide(Shop $shop): Client
    {
        if (!array_key_exists($shop->getId(), $this->apiClients)) {
            $this->apiClients[$shop->getId()] = $this->buildStorefrontApiClient($shop);
        }

        return $this->apiClients[$shop->getId()];
    }

    /**
     * @throws AdyenException
     */
    private function buildStorefrontApiClient(Shop $shop): Client
    {
        $urlPrefix = Environment::LIVE === $this->configuration->getEnvironment($shop)
            ? $this->configuration->getApiUrlPrefix($shop)
            : null;

        $apiClient = new Client();
        $apiClient->setMerchantAccount($this->configuration->getMerchantAccount($shop));
        $apiClient->setXApiKey($this->configuration->getApiKey($shop));
        $apiClient->setEnvironment($this->configuration->getEnvironment($shop), $urlPrefix);
        $apiClient->setLogger($this->logger);

        return $apiClient;
    }
}
