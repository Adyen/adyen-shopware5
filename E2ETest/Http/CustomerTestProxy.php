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
     * @param array $customerData
     * @return array
     * @throws HttpRequestException
     */
    public function saveCustomer(array $customerData): array
    {
        $httpRequest = new HttpRequest("/api/customers", $customerData);
        $response = $this->post($httpRequest)->decodeBodyToArray();

        return $response['success'] ? $response['data'] : [];
    }

    /**
     *
     * Creates request to get all countries from system
     *
     * @throws HttpRequestException
     */
    public function getCustomer(int $customerId): array
    {
        $httpRequest = new HttpRequest("/api/customers/$customerId");
        $response = $this->get($httpRequest)->decodeBodyToArray();

        return $response['success'] ? $response['data'] : [];
    }
}