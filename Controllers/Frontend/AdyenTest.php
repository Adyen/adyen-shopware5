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
     * Handles request by generating data for testing purposes
     *
     * @return void
     */
    public function indexAction(): void
    {
        $payload = $this->Request()->getParams();
        $url = $payload['url'] ?? '';

        try {
            $authorizationService = new AuthorizationService();
            $credentials = $authorizationService->getAuthorizationCredentials();
            $createSeedDataService = new CreateSeedDataService($credentials);

            if ($url !== '') {
                $createSeedDataService->updateBaseUrl($url);
                $this->Response()->setHeader('Content-Type', 'application/json');
                $this->Response()->setBody(
                    json_encode(['message' => 'Url is updated.'])
                );

                return;
            }

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
