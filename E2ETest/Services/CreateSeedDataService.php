<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use AdyenPayment\E2ETest\Http\TestProxy;
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
        $this->shopProxy = new ShopsTestProxy($this->getHttpClient(), 'localhost', $credentials);
        $this->baseUrl = $url;
    }

    /**
     * @throws HttpRequestException
     */
    public function createInitialData(): void
    {
        $this->shopProxy->clearCache();
        parent::createInitialData();
    }

    /**
     * Updates baseUrl in database and default shop name
     *
     * @throws HttpRequestException
     */
    public function updateBaseUrl(): void
    {
        $host = parse_url($this->baseUrl)['host'];
        $name = $this->readFomJSONFile()['subStores'][0]['name'];
        $this->shopProxy->updateBaseUrlAndDefaultShopName(1, $host, $name);
    }

    /**
     * Creates new subStores using json file data
     *
     * @throws HttpRequestException
     */
    public function createSubStores(): void
    {
        $subStores = $this->readFomJSONFile()['subStores'];
        $subStoresArrayLength = count($subStores);
        for ($i = 1; $i < $subStoresArrayLength; $i++) {
            $subStores[$i]['host'] = parse_url($this->baseUrl)['host'];
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