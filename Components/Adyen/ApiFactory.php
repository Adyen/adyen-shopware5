<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Client;
use Adyen\Environment;
use MeteorAdyen\Components\Configuration;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

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

    /** @var Client */
    private $apiClient;

    /**
     * PaymentMethodService constructor.
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
     * @param null $shop
     * @return Client
     * @throws AdyenException
     */
    public function create($shop = null)
    {
        if (!$this->apiClient) {
            $urlPrefix = null;
            if ($this->configuration->getEnvironment($shop) === Environment::LIVE) {
                $urlPrefix = $this->configuration->getApiUrlPrefix($shop);
            }

            $this->apiClient = new Client();
            $this->apiClient->setXApiKey($this->configuration->getApiKey($shop));
            $this->apiClient->setEnvironment($this->configuration->getEnvironment($shop), $urlPrefix);
            $this->apiClient->setLogger($this->getLogger());
        }

        return $this->apiClient;
    }

    /**
     * @return Logger|LoggerInterface
     * @throws \Exception
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
     * @throws \Exception
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
