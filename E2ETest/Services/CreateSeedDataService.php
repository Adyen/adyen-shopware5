<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use AdyenPayment\E2ETest\Http\CacheTestProxy;
use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\E2ETest\Http\ShopsTestProxy;
use Adyen\Core\BusinessLogic\E2ETest\Services\CreateSeedDataService as BaseCreateSeedDataService;

/**
 * Class CreateSeedDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class CreateSeedDataService extends BaseCreateSeedDataService
{
    /**
     * @var ShopsTestProxy
     */
    private $shopProxy;
    /**
     * @var CacheTestProxy
     */
    private $cacheProxy;

    /**
     * CreateSeedDataService constructor.
     *
     * @param string $credentials
     */
    public function __construct(string $credentials)
    {
        $this->shopProxy = new ShopsTestProxy($this->getHttpClient(), 'localhost', $credentials);
        $this->cacheProxy = new CacheTestProxy($this->getHttpClient(), 'localhost', $credentials);
    }

    /**
     * @throws HttpRequestException
     */
    public function createInitialData(): void
    {
        $this->cacheProxy->clearCache();
        parent::createInitialData();
    }

    /**
     * Updates baseUrl in database
     *
     * @throws HttpRequestException
     */
    public function updateBaseUrl(string $url): void
    {
        $subStoresDataFromShop = $this->shopProxy->getSubStores();
        if (array_key_exists('data', $subStoresDataFromShop)) {
            $subStores = $subStoresDataFromShop['data'];
            $body = ['host' => parse_url($url)['host']];
            foreach ($subStores as $subStore) {
                $this->shopProxy->updateShop($subStore['id'], $body);
            }
        }
    }

    /**
     * Updates default shop name in database
     *
     * @throws HttpRequestException
     */
    public function updateDefaultShopName(): void
    {
        $body = ['name' => $this->readFomJSONFile()['subStores'][0]['name']];
        $this->shopProxy->updateShop(1, $body);
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

        $subStores = $this->readFomJSONFile()['subStores'];
        $subStoresArrayLength = count($subStores);
        for ($i = 1; $i < $subStoresArrayLength; $i++) {
            $this->shopProxy->createSubStore($subStores[$i]);
        }
    }

    /**
     * Reads from json file
     *
     * @return array
     */
    private function readFomJSONFile(): array
    {
        $jsonString = file_get_contents(
            './custom/plugins/AdyenPayment/E2ETest/Data/test_data.json',
            FILE_USE_INCLUDE_PATH
        );

        return json_decode($jsonString, true);
    }

    /**
     * @return HttpClient
     */
    private function getHttpClient(): HttpClient
    {
        return ServiceRegister::getService(HttpClient::class);
    }
}