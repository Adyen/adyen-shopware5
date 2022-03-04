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
 * Class ApiFactory.
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
        return $this->createClient(
            $this->configuration->getMerchantAccount($shop),
            $this->configuration->getApiKey($shop),
            $this->configuration->getEnvironment($shop),
            $this->configuration->getApiUrlPrefix($shop)
        );
    }

    private function createClient(
        string $merchantAccount,
        string $apiKey,
        string $environment,
        ?string $prefix = null
    ): Client {
        $urlPrefix = Environment::LIVE === $environment ? $prefix : null;

        $adyenClient = new Client();
        $adyenClient->setMerchantAccount($merchantAccount);
        $adyenClient->setXApiKey($apiKey);
        $adyenClient->setEnvironment($environment, $urlPrefix);
        $adyenClient->setLogger($this->logger);

        return $adyenClient;
    }
}
