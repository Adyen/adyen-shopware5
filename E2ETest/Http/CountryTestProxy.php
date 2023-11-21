<?php

namespace AdyenPayment\E2ETest\Http;

use Adyen\Core\BusinessLogic\AdyenAPI\Http\Requests\HttpRequest;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;

/**
 * Class CountryTestProxy
 *
 * @package AdyenPayment\E2ETest\Http
 */
class CountryTestProxy extends TestProxy
{
    /**
     * Creates request to update base url and default shop name
     *
     * @throws HttpRequestException
     */
    public function activateCountry(int $countryId): void
    {
        $httpRequest = new HttpRequest(
            "/api/countries/$countryId",
            [
                'active' => true
            ]
        );
        $this->put($httpRequest)->decodeBodyToArray();
    }

    /**
     *
     * Creates request to get all countries from system
     *
     * @throws HttpRequestException
     */
    public function getCountries(): array
    {
        $httpRequest = new HttpRequest("/api/countries");

        return $this->get($httpRequest)->decodeBodyToArray();
    }
}