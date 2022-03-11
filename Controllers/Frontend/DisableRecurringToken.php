<?php

declare(strict_types=1);

use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandler;
use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandlerInterface;
use AdyenPayment\Http\Response\ApiJsonResponse;
use AdyenPayment\Http\Response\FrontendJsonResponse;
use Shopware\Components\CSRFGetProtectionAware;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

// @TODO: pending test with SW5 PSR-1 autoloading
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Shopware_Controllers_Frontend_DisableRecurringToken extends Enlight_Controller_Action
    implements CSRFGetProtectionAware
{
    private ApiJsonResponse $frontendJsonResponse;
    private DisableTokenRequestHandlerInterface $disableTokenRequestHandler;

    public function preDispatch(): void
    {
        $this->frontendJsonResponse = $this->get(FrontendJsonResponse::class);
        $this->disableTokenRequestHandler = $this->get(DisableTokenRequestHandler::class);
    }

    public function disabledAction(): void
    {
        try {
            if (!$this->Request()->isPost()) {
                $this->sendJsonBadRequestResponse('Invalid method.');
                return;
            }

            $recurringToken = $this->Request()->getParams()['recurringToken'] ?? '';
            if ('' === $recurringToken) {
                $this->sendJsonBadRequestResponse('Missing recurring token param.');
                return;
            }

            $result = $this->disableTokenRequestHandler->disableToken($recurringToken, Shopware()->Shop());

            $this->frontendJsonResponse->sendJsonResponse(
                $this->Front(),
                $this->Response(),
                JsonResponse::create(
                    ['error' => !$result->isSuccess(), 'message' => $result->message()],
                    Response::HTTP_OK
                )
            );
        } catch (\Exception $e) {
            $this->sendJsonBadRequestResponse($e->getMessage());
        }
    }

    public function getCSRFProtectedActions()
    {
        return ['disabled'];
    }

    private function sendJsonBadRequestResponse($message): void
    {
        $this->frontendJsonResponse->sendJsonResponse(
            $this->Front(),
            $this->Response(),
            JsonResponse::create(
                ['error' => true, 'message' => $message],
                Response::HTTP_BAD_REQUEST
            )
        );
    }
}