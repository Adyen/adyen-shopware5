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
     * Creates request and updates base url
     *
     * @throws HttpRequestException
     */
    public function updateBaseUrl(int $shopId, string $host): void
    {
        $httpRequest = new HttpRequest("/api/shops/$shopId", ['host' => $host]);
        $this->put($httpRequest)->decodeBodyToArray();
    }
}