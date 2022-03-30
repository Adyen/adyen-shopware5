<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Response;

use Enlight_Controller_Front;
use Enlight_Controller_Response_ResponseHttp;
use Symfony\Component\HttpFoundation\JsonResponse;

interface ApiJsonResponse
{
    public function sendJsonResponse(
        Enlight_Controller_Front $frontController,
        Enlight_Controller_Response_ResponseHttp $httpResponse,
        JsonResponse $response
    ): Enlight_Controller_Response_ResponseHttp;

    public function sendJsonBadRequestResponse(
        Enlight_Controller_Front $frontController,
        Enlight_Controller_Response_ResponseHttp $httpResponse,
        string $message
    ): Enlight_Controller_Response_ResponseHttp;
}
