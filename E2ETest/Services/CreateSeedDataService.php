<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use AdyenPayment\E2ETest\Http\TestProxy;
use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\E2ETest\Http\ShopsTestProxy;
use Adyen\Core\Infrastructure\Logger;

/**
 * Class CreateSeedDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class CreateSeedDataService
{
    /**
     * @var TestProxy
     */
    private $shopProxy;
    /**
     * @var string
     */
    private $baseUrl;

    /**
     * CreateSeedDataService constructor.
     *
     * @param string $url
     * @param string $credentials
     */
    public function __construct(string $url, string $credentials)
    {
        $this->shopProxy = new ShopsTestProxy($this->getHttpClient(), $url, $credentials);
        $this->baseUrl = $url;
    }

    /**
     * Creates initial data
     *
     * @return bool
     */
    public function createInitialData(): bool
    {
        try {
            $this->updateBaseUrl();
        } catch (HttpRequestException $exception) {
            Logger::logError('Initial data creation failed. ' . $exception->getMessage());

            return false;
        }

        return true;
    }

    /**
     * Updates baseUrl in database
     *
     * @throws HttpRequestException
     */
    public function updateBaseUrl(): void
    {
        $host = parse_url($this->baseUrl)['host'];
        $this->shopProxy->updateBaseUrl(1, $host);
    }

    /**
     * @return HttpClient
     */
    private function getHttpClient(): HttpClient
    {
        return ServiceRegister::getService(HttpClient::class);
    }
}