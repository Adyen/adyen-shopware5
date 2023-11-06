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
     * Creates request to update base url and default shop name
     *
     * @throws HttpRequestException
     */
    public function clearCache(): void
    {
        $httpRequest = new HttpRequest(
            "/api/caches"
        );
        $this->delete($httpRequest)->decodeBodyToArray();
    }
    /**
     * Creates request to update base url and default shop name
     *
     * @throws HttpRequestException
     */
    public function updateBaseUrlAndDefaultShopName(int $shopId, string $host, string $name): void
    {
        $httpRequest = new HttpRequest(
            "/api/shops/$shopId",
            [
//                'host' => $host,
                'name' => $name
            ]
        );
        $this->put($httpRequest)->decodeBodyToArray();
    }

    /**
     *
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