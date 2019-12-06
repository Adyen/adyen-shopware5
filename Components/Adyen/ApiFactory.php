<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Client;
use Adyen\Environment;
use MeteorAdyen\Components\Configuration;
use Psr\Log\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Shop\Repository as ShopRepository;
use Shopware\Models\Shop\Shop;

/**
 * Class ApiFactory
 * @package MeteorAdyen\Components\Adyen
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
     * @var ShopRepository
     */
    private $shopRepository;

    /**
     * @var Client[]
     */
    private $apiClients;

    /**
     * ApiFactory constructor.
     * @param ModelManager $modelManager
     * @param Configuration $configuration
     * @param LoggerInterface $logger
     */
    public function __construct(
        ModelManager $modelManager,
        Configuration $configuration,
        LoggerInterface $logger
    ) {
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->shopRepository = $modelManager->getRepository(Shop::class);
        $this->apiClients = [];
    }

    /**
     * @param null|Shop $shop
     * @return Client
     * @throws AdyenException
     */
    public function create($shop = null)
    {
        if (!$shop) {
            $shop = $this->shopRepository->getDefault();
        }

        if (!$this->apiClients[$shop->getId()]) {
            $urlPrefix = null;
            if ($this->configuration->getEnvironment($shop) === Environment::LIVE) {
                $urlPrefix = $this->configuration->getApiUrlPrefix($shop);
            }

            $apiClient = new Client();
            $apiClient->setXApiKey($this->configuration->getApiKey($shop));
            $apiClient->setEnvironment($this->configuration->getEnvironment($shop), $urlPrefix);
            $apiClient->setLogger($this->logger);

            $this->apiClients[$shop->getId()] = $apiClient;
        }

        return $this->apiClients[$shop->getId()];
    }
}
