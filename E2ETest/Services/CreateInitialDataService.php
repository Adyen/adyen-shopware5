<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\BusinessLogic\E2ETest\Services\CreateIntegrationDataService;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\E2ETest\Http\CacheTestProxy;
use AdyenPayment\E2ETest\Http\ShopsTestProxy;

/**
 * Class CreateInitialDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class CreateInitialDataService extends BaseCreateSeedDataService
{
    /**
     * @var string
     */
    private $baseUrl;

    /**
     * CreateSeedDataService constructor.
     *
     * @param string $url
     */
    public function __construct(string $url)
    {
        $this->baseUrl = $url;
    }

    /**
     * @throws HttpRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function createInitialData(): void
    {
        $this->getCacheTestProxy()->clearCache();
        $this->updateBaseUrlAndDefaultShopName();
        $this->createSubStores();
        $this->getCreateIntegrationDataService()->saveTestHostname($this->baseUrl);
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
        $this->getShopsTestProxy()->updateSubStore(1,
            [
                'host' => $host,
                'name' => $name,
                'secure' => true
            ]
        );
    }

    /**
     * Creates new subStores using json file data
     *
     * @throws HttpRequestException
     */
    public function createSubStores(): void
    {
        $subStoresFromShop = $this->getShopsTestProxy()->getSubStores();
        if (array_key_exists('total', $subStoresFromShop) && $subStoresFromShop['total'] > 1) {
            return;
        }

        $subStores = $this->readFromJSONFile()['subStores'];
        $subStoresArrayLength = count($subStores);
        for ($i = 1; $i < $subStoresArrayLength; $i++) {
            $subStores[$i]['host'] = parse_url($this->baseUrl)['host'];
            $this->getShopsTestProxy()->createSubStore($subStores[$i]);
        }
    }

    /**
     * @return CacheTestProxy
     */
    private function getCacheTestProxy(): CacheTestProxy
    {
        return ServiceRegister::getService(CacheTestProxy::class);
    }

    /**
     * @return ShopsTestProxy
     */
    private function getShopsTestProxy(): ShopsTestProxy
    {
        return ServiceRegister::getService(ShopsTestProxy::class);
    }

    /**
     * Returns CreateIntegrationDataService instance
     *
     * @return CreateIntegrationDataService
     */
    private function getCreateIntegrationDataService(): CreateIntegrationDataService
    {
        return ServiceRegister::getService(CreateIntegrationDataService::class);
    }
}