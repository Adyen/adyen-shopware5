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
use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\Controllers\Common\AjaxResponseSetter;
use AdyenPayment\E2ETest\Exception\InvalidDataException;
use AdyenPayment\E2ETest\Http\CacheTestProxy;
use AdyenPayment\E2ETest\Http\CountryTestProxy;
use AdyenPayment\E2ETest\Http\CustomerTestProxy;
use AdyenPayment\E2ETest\Http\OrderTestProxy;
use AdyenPayment\E2ETest\Http\PaymentMethodsTestProxy;
use AdyenPayment\E2ETest\Http\ShopsTestProxy;
use AdyenPayment\E2ETest\Http\UserTestProxy;
use AdyenPayment\E2ETest\Repositories\ShopRepository;
use AdyenPayment\E2ETest\Services\AdyenAPIService;
use AdyenPayment\E2ETest\Services\AuthorizationService;
use AdyenPayment\E2ETest\Services\CreateCheckoutDataService;
use AdyenPayment\E2ETest\Services\CreateInitialDataService;
use AdyenPayment\E2ETest\Services\CreateWebhooksDataService;
use AdyenPayment\E2ETest\Services\TransactionLogService;
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
     * @throws ORMException
     * @throws OptimisticLockException|HttpRequestException
     * @throws QueryFilterInvalidParamException
     */
    public function indexAction(): void
    {
        $payload = $this->Request()->getParams();

        if ($payload['merchantReference'] && $payload['eventCode']) {
            $this->verifyWebhookStatus($payload['merchantReference'], $payload['eventCode']);

            return;
        }

        $url = $payload['url'] ?? '';
        $testApiKey = $payload['testApiKey'] ?? '';
        $liveApiKey = $payload['liveApiKey'] ?? '';

        try {
            if ($url === '' || $testApiKey === '' || $liveApiKey === '') {
                throw new InvalidDataException('Url, test api key and live api key are required parameters.');
            }

            $this->verifyManagementAPI($testApiKey, $liveApiKey);
            $credentials = $this->getAuthorizationCredentials();
            $host = (new ShopRepository())->getDefaultShopHost() ?? 'localhost';
            $this->registerProxies($credentials, $host);
            $this->createInitialSeedData($url);
            $customerId = $this->createCheckoutSeedData($testApiKey);
            $createWebhookDataService = new CreateWebhooksDataService();
            $webhookData = $createWebhookDataService->getWebhookAuthorizationData();
            $ordersMerchantReferenceAndAmount = $createWebhookDataService->crateOrderPrerequisitesData($customerId);
            $this->Response()->setBody(
                json_encode(array_merge(
                    $ordersMerchantReferenceAndAmount,
                    $webhookData,
                    ['message' => 'The initial data setup was successfully completed.']
                ))
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
        } finally {
            $this->Response()->setHeader('Content-Type', 'application/json');
        }
    }

    /**
     * Calls service to verify if OrderUpdate queue item is in terminal state
     *
     * @throws QueryFilterInvalidParamException
     */
    private function verifyWebhookStatus($merchantReference, $eventCode): void
    {
        $transactionLogService = new TransactionLogService();

        die(json_encode(array_merge(
            ['finished' => $transactionLogService->findLogsByMerchantReference($merchantReference, $eventCode)]
        )));
    }

    /**
     * Registers proxies for rest api requests
     *
     * @param string $credentials
     * @param string $host
     * @return void
     */
    private function registerProxies(string $credentials, string $host): void
    {
        ServiceRegister::registerService(
            CacheTestProxy::class,
            static function () use ($credentials, $host) {
                return new CacheTestProxy(
                    ServiceRegister::getService(HttpClient::class),
                    $host,
                    $credentials
                );
            }
        );

        ServiceRegister::registerService(
            ShopsTestProxy::class,
            static function () use ($credentials, $host) {
                return new ShopsTestProxy(
                    ServiceRegister::getService(HttpClient::class),
                    $host,
                    $credentials
                );
            }
        );

        ServiceRegister::registerService(
            CountryTestProxy::class,
            static function () use ($credentials, $host) {
                return new CountryTestProxy(
                    ServiceRegister::getService(HttpClient::class),
                    $host,
                    $credentials
                );
            }
        );

        ServiceRegister::registerService(
            CustomerTestProxy::class,
            static function () use ($credentials, $host) {
                return new CustomerTestProxy(
                    ServiceRegister::getService(HttpClient::class),
                    $host,
                    $credentials
                );
            }
        );

        ServiceRegister::registerService(
            OrderTestProxy::class,
            static function () use ($credentials, $host) {
                return new OrderTestProxy(
                    ServiceRegister::getService(HttpClient::class),
                    $host,
                    $credentials
                );
            }
        );

        ServiceRegister::registerService(
            PaymentMethodsTestProxy::class,
            static function () use ($credentials, $host) {
                return new PaymentMethodsTestProxy(
                    ServiceRegister::getService(HttpClient::class),
                    $host,
                    $credentials);
            }
        );

        ServiceRegister::registerService(
            UserTestProxy::class,
            static function () use ($credentials, $host) {
                return new UserTestProxy(
                    ServiceRegister::getService(HttpClient::class),
                    $host,
                    $credentials
                );
            }
        );
    }

    /**
     * Calls service to verify if management api is stable
     *
     * @param string $testApiKey
     * @param string $liveApiKey
     * @return void
     * @throws HttpRequestException
     */
    private function verifyManagementApi(string $testApiKey, string $liveApiKey): void
    {
        (new AdyenAPIService())->verifyManagementAPI($testApiKey, $liveApiKey);
    }

    /**
     * Calls service to create authorization credentials for rest api
     *
     * @return string
     * @throws Exception
     */
    private function getAuthorizationCredentials(): string
    {
        return (new AuthorizationService())->getAuthorizationCredentials();
    }

    /**
     * Calls service to create initial seed data
     *
     * @throws QueryFilterInvalidParamException
     * @throws HttpRequestException
     */
    private function createInitialSeedData(string $url): void
    {
        (new CreateInitialDataService($url))->createInitialData();
    }

    /**
     * Calls service to create checkout seed data and returns existing customer id
     *
     * @param string $testApiKey
     * @return string
     *
     * @throws ApiCredentialsDoNotExistException
     * @throws ConnectionSettingsNotFoundException
     * @throws HttpRequestException
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
    private function createCheckoutSeedData(string $testApiKey): string
    {
        return (new CreateCheckoutDataService())->crateCheckoutPrerequisitesData($testApiKey);
    }
}
