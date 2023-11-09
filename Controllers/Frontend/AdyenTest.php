<?php

use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\E2ETest\Services\AuthorizationService;
use AdyenPayment\E2ETest\Services\CreateSeedDataService;
use Shopware\Components\CSRFWhitelistAware;

/**
 * Class Shopware_Controllers_Frontend_AdyenTest
 */
class Shopware_Controllers_Frontend_AdyenTest extends Enlight_Controller_Action implements CSRFWhitelistAware
{
    use AjaxResponseSetter;

    /**
     * @param $request
     * @param $response
     *
     * @return void
     *
     * @throws Enlight_Event_Exception
     * @throws Enlight_Exception
     * @throws Exception
     */
    public function initController($request, $response): void
    {
        $this->Front()->Plugins()->JsonRequest()
            ->setParseInput();

        parent::initController($request, $response);
    }

    /**
     * Returns a list with actions which should not be validated for CSRF protection
     *
     * @return string[]
     */
    public function getWhitelistedCSRFActions(): array
    {
        return ['index'];
    }

    /**
     * Handles request by generating seed data for testing purposes
     *
     * @return void
     *
     * @throws JsonException
     */
    public function indexAction(): void
    {
        $payload = $this->Request()->getParams();
        $url = $payload['url'] ?? '';

        try {
            if($url === ''){
                throw new RuntimeException('Url is a required field.');
            }

            $authorizationService = new AuthorizationService();
            $credentials = $authorizationService->getAuthorizationCredentials();
            $createSeedDataService = new CreateSeedDataService($url, $credentials);
            $createSeedDataService->createInitialData();
        } catch (Exception $exception) {
            $this->Response()->setStatusCode(400);
            $this->Response()->setHeader('Content-Type', 'application/json');
            $this->Response()->setBody(
                json_encode(['message' => $exception->getMessage()])
            );

            return;
        }

        $this->Response()->setHeader('Content-Type', 'application/json');
        $this->Response()->setBody(
            json_encode(['message' => 'The initial data setup was successfully completed.'])
        );
    }
}
