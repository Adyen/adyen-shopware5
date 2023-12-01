<?php

use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\E2ETest\Exception\InvalidDataException;
use AdyenPayment\E2ETest\Services\AdyenAPIService;
use AdyenPayment\E2ETest\Services\AuthorizationService;
use AdyenPayment\E2ETest\Services\CreateCheckoutDataService;
use AdyenPayment\E2ETest\Services\CreateInitialDataService;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
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
     * Handles request by generating initial seed data for testing purposes
     *
     * @return void
     */
    public function indexAction(): void
    {
        $payload = $this->Request()->getParams();
        $url = $payload['url'] ?? '';
        $testApiKey = $payload['testApiKey'] ?? '';
        $liveApiKey = $payload['liveApiKey'] ?? '';

        try {
            if ($url === '' || $testApiKey === '' || $liveApiKey === '') {
                throw new InvalidDataException('Url, test api key and live api key are required parameters.');
            }

            $adyenApiService = new AdyenAPIService();
            $adyenApiService->verifyManagementAPI($testApiKey, $liveApiKey);
            $authorizationService = new AuthorizationService();
            $credentials = $authorizationService->getAuthorizationCredentials();
            $createSeedDataService = new CreateInitialDataService($url, $credentials);
            $createSeedDataService->createInitialData();
            $createCheckoutDataService = new CreateCheckoutDataService($credentials);
            $createCheckoutDataService->crateCheckoutPrerequisitesData($testApiKey);
            $this->Response()->setBody(
                json_encode(['message' => 'The initial data setup was successfully completed.'])
            );
        } catch (InvalidDataException $exception) {
            $this->Response()->setStatusCode(400);
            $this->Response()->setBody(
                json_encode(['message' => $exception->getMessage()])
            );
        } catch (HttpRequestException $exception) {
            $this->Response()->setStatusCode(503);
            $this->Response()->setBody(
                json_encode(['message' => $exception->getMessage()])
            );
        } catch (Exception $exception) {
            $this->Response()->setStatusCode(500);
            $this->Response()->setBody(
                json_encode(['message' => $exception->getMessage()])
            );
        }  finally {
            $this->Response()->setHeader('Content-Type', 'application/json');
        }
    }
}
