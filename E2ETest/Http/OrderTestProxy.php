<?php

namespace AdyenPayment\E2ETest\Http;

use Adyen\Core\BusinessLogic\AdyenAPI\Http\Requests\HttpRequest;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class OrderTestProxy
 *
 * @package AdyenPayment\E2ETest\Http
 */
class OrderTestProxy extends TestProxy
{
    /**
     * Creates request to save order in database
     *
     * @throws HttpRequestException
     */
    public function createOrder(array $orderData): void
    {
        $httpRequest = new HttpRequest(
            "/api/orders",
            $orderData
        );
        $this->post($httpRequest)->decodeBodyToArray();
    }
}