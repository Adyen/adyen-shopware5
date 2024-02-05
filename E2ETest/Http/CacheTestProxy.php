<?php

namespace AdyenPayment\E2ETest\Http;

use Adyen\Core\BusinessLogic\AdyenAPI\Http\Requests\HttpRequest;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class CacheTestProxy
 *
 * @package AdyenPayment\E2ETest\Http
 */
class CacheTestProxy extends TestProxy
{
    /**
     * Creates request to clear config cache
     *
     * @throws HttpRequestException
     */
    public function clearCache(): void
    {
        $httpRequest = new HttpRequest(
            "/api/caches",
            [
                'id' => 'config',
            ]
        );
        $this->delete($httpRequest)->decodeBodyToArray();
    }
}