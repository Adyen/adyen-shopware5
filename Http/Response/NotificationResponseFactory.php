<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Response;

use Enlight_Controller_Request_RequestHttp;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Zend_Json;

class NotificationResponseFactory
{
    public static function accepted(): JsonResponse
    {
        return new JsonResponse('[accepted]', Response::HTTP_ACCEPTED);
    }

    public static function unauthorized(string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_UNAUTHORIZED);
    }

    public static function badRequest(string $message): JsonResponse
    {
        return new JsonResponse([
            'success' => false,
            'message' => $message,
        ], Response::HTTP_BAD_REQUEST);
    }

    public static function fromShopwareResponse(Enlight_Controller_Request_RequestHttp $request, $data): JsonResponse
    {
        $pretty = (bool) $request->getParam('pretty', false);
        if (true === $pretty) {
            return new JsonResponse(Zend_Json::prettyPrint($data));
        }

        return new JsonResponse(Zend_Json::encode(array_map(static function($value) {
            return $value instanceof \DateTimeInterface ? $value->format(\DateTime::ISO8601) : $value;
        }, $data)));
    }
}
