<?php

use Adyen\Core\BusinessLogic\AdyenAPI\Exceptions\ConnectionSettingsNotFoundException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\ApiCredentialsDoNotExistException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\ApiKeyCompanyLevelException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\EmptyConnectionDataException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\EmptyStoreException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidAllowedOriginException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidApiKeyException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidConnectionSettingsException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\InvalidModeException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\MerchantIdChangedException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\ModeChangedException;
use Adyen\Core\BusinessLogic\Domain\Connection\Exceptions\UserDoesNotHaveNecessaryRolesException;
use Adyen\Core\BusinessLogic\Domain\Merchant\Exceptions\ClientKeyGenerationFailedException;
use Adyen\Core\BusinessLogic\Domain\Payment\Exceptions\PaymentMethodDataEmptyException;
use Adyen\Core\BusinessLogic\Domain\Webhook\Exceptions\FailedToGenerateHmacException;
use Adyen\Core\BusinessLogic\Domain\Webhook\Exceptions\FailedToRegisterWebhookException;
use Adyen\Core\BusinessLogic\Domain\Webhook\Exceptions\MerchantDoesNotExistException;
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
        return ['index', 'createCheckoutPrerequisites'];
    }

    /**
     * Handles request by generating seed data for testing purposes
     *
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException|HttpRequestException
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
        } finally {
            $this->Response()->setHeader('Content-Type', 'application/json');
        }
    }

    /**
     * @return void
     * @throws ConnectionSettingsNotFoundException
     * @throws ApiCredentialsDoNotExistException
     * @throws ApiKeyCompanyLevelException
     * @throws EmptyConnectionDataException
     * @throws EmptyStoreException
     * @throws InvalidAllowedOriginException
     * @throws InvalidApiKeyException
     * @throws InvalidConnectionSettingsException
     * @throws InvalidModeException
     * @throws MerchantIdChangedException
     * @throws ModeChangedException
     * @throws UserDoesNotHaveNecessaryRolesException
     * @throws ClientKeyGenerationFailedException
     * @throws PaymentMethodDataEmptyException
     * @throws FailedToGenerateHmacException
     * @throws FailedToRegisterWebhookException
     * @throws MerchantDoesNotExistException
     */
    public function createCheckoutPrerequisitesAction(): void
    {
        $payload = $this->Request()->getParams();
        $testApiKey = $payload['testApiKey'] ?? '';

        try {
            if ($testApiKey === '') {
                throw new InvalidDataException('Test api key is required parameter.');
            }

            $authorizationService = new AuthorizationService();
            $credentials = $authorizationService->getAuthorizationCredentials();
            $createCheckoutDataService = new CreateCheckoutDataService($credentials);
            $createCheckoutDataService->crateCheckoutPrerequisitesData($testApiKey);
            $this->Response()->setHeader('Content-Type', 'application/json');
            $this->Response()->setBody(
                json_encode(['message' => 'The checkout prerequisites data are sucessfully saved.'])
            );
        }catch (InvalidDataException $exception) {
            $this->Response()->setStatusCode(400);
            $this->Response()->setBody(
                json_encode(['message' => $exception->getMessage()])
            );
        } catch (HttpRequestException $exception) {
            $this->Response()->setStatusCode(503);
            $this->Response()->setBody(
                json_encode(['message' => $exception->getMessage()])
            );
        } finally {
            $this->Response()->setHeader('Content-Type', 'application/json');
        }

    }
}
