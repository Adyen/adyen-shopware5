<?php

declare(strict_types=1);

use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandler;
use AdyenPayment\AdyenApi\Recurring\DisableTokenRequestHandlerInterface;
use AdyenPayment\Http\Response\ApiJsonResponse;
use AdyenPayment\Http\Response\FrontendJsonResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

// @TODO: pending test with SW5 PSR-1 autoloading
//phpcs:ignore PSR1.Classes.ClassDeclaration.MissingNamespace, Squiz.Classes.ValidClassName.NotCamelCaps
class Shopware_Controllers_Frontend_DisableRecurringToken extends Enlight_Controller_Action
{
    private ApiJsonResponse $frontendJsonResponse;
    private DisableTokenRequestHandlerInterface $disableTokenRequestHandler;
    private Shopware_Components_Snippet_Manager $snippets;

    public function preDispatch(): void
    {
        $this->frontendJsonResponse = $this->get(FrontendJsonResponse::class);
        $this->disableTokenRequestHandler = $this->get(DisableTokenRequestHandler::class);
        $this->snippets = $this->get('snippets');
    }

    public function disabledAction(): void
    {
        try {
            if (!$this->Request()->isPost()) {
                $this->frontendJsonResponse->sendJsonBadRequestResponse(
                    $this->Front(),
                    $this->Response(),
                    $this->snippets->getNamespace('adyen/checkout/error')->get(
                        'disableTokenInvalidMethodMessage',
                        'Invalid method.',
                        true
                    )
                );

                return;
            }

            $recurringToken = $this->Request()->getParams()['recurringToken'] ?? '';
            if ('' === $recurringToken) {
                $this->frontendJsonResponse->sendJsonBadRequestResponse(
                    $this->Front(),
                    $this->Response(),
                    $this->snippets->getNamespace('adyen/checkout/error')->get(
                        'disableTokenMissingRecurringTokenMessage',
                        'Missing recurring token param.',
                        true
                    )
                );

                return;
            }

            $result = $this->disableTokenRequestHandler->disableToken($recurringToken, Shopware()->Shop());
            if (!$result->isSuccess()) {
               $this->frontendJsonResponse->sendJsonBadRequestResponse(
                   $this->Front(),
                   $this->Response(),
                   $this->snippets->getNamespace('adyen/checkout/error')->get(
                       $result->message()
                   )
               );

               return;
            }

            $this->frontendJsonResponse->sendJsonResponse(
                $this->Front(),
                $this->Response(),
                JsonResponse::create(null, Response::HTTP_NO_CONTENT)
            );
        } catch (\Exception $e) {
            $this->frontendJsonResponse->sendJsonBadRequestResponse($this->Front(), $this->Response(), $e->getMessage());
        }
    }
}