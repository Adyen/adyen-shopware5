<?php

namespace AdyenPayment\E2ETest\Http;

use Adyen\Core\BusinessLogic\AdyenAPI\Http\Requests\HttpRequest;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class ShopsTestProxy
 *
 * @package AdyenPayment\E2ETest\Http
 */
class ShopsTestProxy extends TestProxy
{
    /**
     * Creates request to update subStore
     *
     * @throws HttpRequestException
     */
    public function updateSubStore(int $shopId, array $shopData): void
    {
        $httpRequest = new HttpRequest(
            "/api/shops/$shopId",
            $shopData
        );
        $this->put($httpRequest)->decodeBodyToArray();
    }

    /**
     * Creates request to get exist subStores from system
     *
     * @throws HttpRequestException
     */
    public function getSubStores(): array
    {
        $httpRequest = new HttpRequest("/api/shops");

        return $this->get($httpRequest)->decodeBodyToArray();
    }

    /**
     * Creates request to add new subStore
     *
     * @throws HttpRequestException
     */
    public function createSubStore(array $subStoreData): void
    {
        $httpRequest = new HttpRequest("/api/shops", $subStoreData);
        $this->post($httpRequest)->decodeBodyToArray();
    }
}