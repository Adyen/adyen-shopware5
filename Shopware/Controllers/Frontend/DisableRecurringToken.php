<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Controllers\Frontend;

use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandler;
use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandlerInterface;
use AdyenPayment\Http\Response\ApiJsonResponse;
use AdyenPayment\Http\Response\FrontendJsonResponse;
use Shopware\Components\CSRFGetProtectionAware;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class DisableRecurringToken extends \Enlight_Controller_Action implements CSRFGetProtectionAware
{
    private ApiJsonResponse $frontendJsonResponse;
    private DisableTokenRequestHandlerInterface $disableTokenRequestHandler;

    public function preDispatch(): void
    {
        $this->frontendJsonResponse = $this->get(FrontendJsonResponse::class);
        $this->disableTokenRequestHandler = $this->get(DisableTokenRequestHandler::class);
    }

    /**
     * POST: /disabled
     */
    public function disabled(): void
    {
        try {
            $recurringToken = $this->Request()->getParams()['recurringToken'] ?? '';

            if ('' === $recurringToken) {
                $this->frontendJsonResponse->sendJsonResponse(
                    $this->Front(),
                    $this->Response(),
                    JsonResponse::create(
                        ['error' => true, 'message' => 'Missing recurring token param.'],
                        Response::HTTP_BAD_REQUEST
                    )
                );
                return;
            }

            $result = $this->disableTokenRequestHandler->disableToken($recurringToken, Shopware()->Shop());

            $this->frontendJsonResponse->sendJsonResponse(
                $this->Front(),
                $this->Response(),
                JsonResponse::create(
                    ['error' => !$result->isSuccess(), 'message' => $result->message()], Response::HTTP_OK
                )
            );
        } catch (\Exception $e) {
            $this->frontendJsonResponse->sendJsonResponse(
                $this->Front(),
                $this->Response(),
                JsonResponse::create(['error' => true, 'message' => $e->getMessage()], Response::HTTP_BAD_REQUEST)
            );
        }
    }

    public function getCSRFProtectedActions()
    {
        return ['disabled'];
    }
}