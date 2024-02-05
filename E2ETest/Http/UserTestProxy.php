<?php

namespace AdyenPayment\E2ETest\Http;

use Adyen\Core\BusinessLogic\AdyenAPI\Http\Requests\HttpRequest;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class UserTestProxy
 *
 * @package AdyenPayment\E2ETest\Http
 */
class UserTestProxy extends TestProxy
{
    /**
     * Creates request to create user in database
     *
     * @throws HttpRequestException
     */
    public function createUser(array $userData): array
    {
        $httpRequest = new HttpRequest(
            "/api/users",
            $userData
        );

        return $this->post($httpRequest)->decodeBodyToArray();
    }
}