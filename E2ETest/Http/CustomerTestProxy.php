<?php

namespace AdyenPayment\E2ETest\Http;

use Adyen\Core\BusinessLogic\AdyenAPI\Http\Requests\HttpRequest;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class CustomerTestProxy
 *
 * @package AdyenPayment\E2ETest\Http
 */
class CustomerTestProxy extends TestProxy
{
    /**
     * Creates request to save new customer in database
     *
     * @throws HttpRequestException
     */
    public function saveCustomer(array $customerData): void
    {
        $httpRequest = new HttpRequest("/api/customers", $customerData);
        $this->post($httpRequest)->decodeBodyToArray();
    }
}