<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Adyen;

use Adyen\Client;
use MeteorAdyen\Components\Configuration;

/**
 * Class ApiFactory
 * @package MeteorAdyen\Components\Adyen
 */
class ApiFactory
{
    /**
     * @var ConfigurationService
     */
    private $configuration;

    /** @var Client */
    private $apiClient;

    /**
     * PaymentMethodService constructor.
     * @param Configuration $configuration
     */
    public function __construct(
        Configuration $configuration
    )
    {
        $this->configuration = $configuration;
    }

    /**
     * @return Client
     * @throws \Adyen\AdyenException
     */
    public function create()
    {
        if (!$this->apiClient) {
            $this->apiClient = new Client();
            $this->apiClient->setXApiKey($this->configuration->getApiKey());
            $this->apiClient->setEnvironment($this->configuration->getEnvironment());
        }

        return $this->apiClient;
    }
}