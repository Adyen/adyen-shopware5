<?php

namespace AdyenPayment\E2ETest\Http;

use Adyen\Core\BusinessLogic\AdyenAPI\Http\Requests\HttpRequest;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class OrderTestProxy
 *
 * @package AdyenPayment\E2ETest\Http
 */
class PaymentMethodsTestProxy extends TestProxy
{
    /**
     * Creates request to get payment methods from shop
     *
     * @throws HttpRequestException
     */
    public function getPaymentMethods(): array
    {
        $httpRequest = new HttpRequest(
            "/api/paymentMethods"
        );

        return $this->get($httpRequest)->decodeBodyToArray();
    }
}