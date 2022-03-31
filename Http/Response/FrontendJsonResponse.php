<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Response;

use Enlight_Controller_Front;
use Enlight_Controller_Response_ResponseHttp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

final class FrontendJsonResponse implements ApiJsonResponse
{
    public function sendJsonResponse(
        Enlight_Controller_Front $frontController,
        Enlight_Controller_Response_ResponseHttp $httpResponse,
        JsonResponse $response
    ): Enlight_Controller_Response_ResponseHttp {
        $frontController->Plugins()->ViewRenderer()->setNoRender();

        $httpResponse->setHeader('Content-type', $response->headers->get('Content-Type'), true);
        $httpResponse->setHttpResponseCode($response->getStatusCode());
        $httpResponse->setBody($response->getContent());

        return $httpResponse;
    }

    public function sendJsonBadRequestResponse(
        Enlight_Controller_Front $frontController,
        Enlight_Controller_Response_ResponseHttp $httpResponse,
        string $message
    ): Enlight_Controller_Response_ResponseHttp {
        return $this->sendJsonResponse(
            $frontController,
            $httpResponse,
            JsonResponse::create(
                ['error' => true, 'message' => $message],
                Response::HTTP_BAD_REQUEST
            )
        );
    }
}
