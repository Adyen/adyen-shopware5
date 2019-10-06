<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Client;
use MeteorAdyen\Components\Configuration;
use Psr\Log\LoggerInterface;

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
        Configuration $configuration,
        LoggerInterface $logger
    ) {
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * @return Client
     * @throws AdyenException
     */
    public function create()
    {
        if (!$this->apiClient) {
            $this->apiClient = new Client();
            $this->apiClient->setXApiKey($this->configuration->getApiKey());
            $this->apiClient->setEnvironment($this->configuration->getEnvironment());
            $this->apiClient->setLogger($this->getLogger());
        }

        return $this->apiClient;
    }

    /**
     * @return Logger|LoggerInterface
     */
    public function getLogger()
    {
        if (!isset($this->logger)) {
            $this->logger = $this->createDefaultLogger();
        }

        return $this->logger;
    }

    /**
     * @return Logger
     */
    private function createDefaultLogger()
    {
        $logger = new Logger('adyen-php-api-library');

        $logLevel = Logger::ERROR;
        if ($this->configuration->getDebugLogging()) {
            $logLevel = Logger::DEBUG;
        }

        $logger->pushHandler(new StreamHandler('php://stderr', $logLevel));

        return $logger;
    }
}