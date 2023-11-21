<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\Infrastructure\Configuration\ConfigurationManager;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\E2ETest\Http\ShopsTestProxy;

/**
 * Class CreateInitialDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class CreateInitialDataService extends BaseCreateSeedDataService
{
    /**
     * @var ShopsTestProxy
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
        $this->shopProxy = new ShopsTestProxy($this->getHttpClient(), 'localhost', $credentials);
        $this->baseUrl = $url;
    }

    /**
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function createInitialData(): void
    {
        $this->shopProxy->clearCache();
        $this->updateBaseUrlAndDefaultShopName();
        $this->createSubStores();
        $this->saveTestHostname();
    }

    /**
     * Updates baseUrl in database and default shop name
     *
     * @throws HttpRequestException
     */
    public function updateBaseUrlAndDefaultShopName(): void
    {
        $host = parse_url($this->baseUrl)['host'];
        $name = $this->readFromJSONFile()['subStores'][0]['name'];
        $this->shopProxy->updateBaseUrlAndDefaultShopName(1, $host, $name);
    }

    /**
     * Creates new subStores using json file data
     *
     * @throws HttpRequestException
     */
    public function createSubStores(): void
    {
        $subStoresFromShop = $this->shopProxy->getSubStores();
        if (array_key_exists('total', $subStoresFromShop) && $subStoresFromShop['total'] > 1) {
            return;
        }

        $subStores = $this->readFromJSONFile()['subStores'];
        $subStoresArrayLength = count($subStores);
        for ($i = 1; $i < $subStoresArrayLength; $i++) {
            $subStores[$i]['host'] = parse_url($this->baseUrl)['host'];
            $this->shopProxy->createSubStore($subStores[$i]);
        }
    }

    /**
     * Saves ngrok hostname in database
     *
     * @return void
     * @throws QueryFilterInvalidParamException
     */
    private function saveTestHostname(): void
    {
        $host = parse_url($this->baseUrl)['host'];
        $this->getConfigurationManager()->saveConfigValue('testHostname', $host);
    }

    /**
     * @return HttpClient
     */
    private function getHttpClient(): HttpClient
    {
        return ServiceRegister::getService(HttpClient::class);
    }

    /**
     * @return ConfigurationManager
     */
    private function getConfigurationManager(): ConfigurationManager
    {
        return ServiceRegister::getService(ConfigurationManager::CLASS_NAME);
    }
}