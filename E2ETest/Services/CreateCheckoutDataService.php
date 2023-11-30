<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
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
use Adyen\Core\BusinessLogic\E2ETest\Services\CreateIntegrationDataService;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use AdyenPayment\E2ETest\Http\CountryTestProxy;
use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\E2ETest\Http\CustomerTestProxy;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Shopware\Models\Shop\Currency;
use Shopware\Models\Shop\Repository;
use Shopware\Models\Shop\Shop;
use Shopware\Models\User\User;

/**
 * Class CreateCheckoutDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class CreateCheckoutDataService extends BaseCreateSeedDataService
{
    /**
     * @var CountryTestProxy
     */
    private $countryTestProxy;
    /**
     * @var CustomerTestProxy
     */
    private $customerTestProxy;

    /**
     * CreateCheckoutDataService constructor.
     *
     * @param string $credentials
     */
    public function __construct(string $credentials)
    {
        $this->countryTestProxy = new CountryTestProxy($this->getHttpClient(), 'localhost', $credentials);
        $this->customerTestProxy = new CustomerTestProxy($this->getHttpClient(), 'localhost', $credentials);
    }

    /**
     * @param string $testApiKey
     * @return void
     * @throws ApiCredentialsDoNotExistException
     * @throws ApiKeyCompanyLevelException
     * @throws ClientKeyGenerationFailedException
     * @throws ConnectionSettingsNotFoundException
     * @throws EmptyConnectionDataException
     * @throws EmptyStoreException
     * @throws FailedToGenerateHmacException
     * @throws FailedToRegisterWebhookException
     * @throws HttpRequestException
     * @throws InvalidAllowedOriginException
     * @throws InvalidApiKeyException
     * @throws InvalidConnectionSettingsException
     * @throws InvalidModeException
     * @throws MerchantDoesNotExistException
     * @throws MerchantIdChangedException
     * @throws ModeChangedException
     * @throws PaymentMethodDataEmptyException
     * @throws UserDoesNotHaveNecessaryRolesException
     */
    public function crateCheckoutPrerequisitesData(string $testApiKey): void
    {
        if (count(AdminAPI::get()->connection(1)->getConnectionSettings()->toArray()) > 0) {
            return;
        }

        $this->createIntegrationConfigurations($testApiKey);
        $this->activateCountries();
        $this->createCustomers();
        $currencies = $this->createCurrenciesInDatabase();
        $this->addCurrenciesInSubStore($currencies);
    }

    /**
     * Creates the integration configuration - authorization data and payment methods
     *
     * @throws EmptyConnectionDataException
     * @throws ApiKeyCompanyLevelException
     * @throws MerchantDoesNotExistException
     * @throws InvalidModeException
     * @throws EmptyStoreException
     * @throws InvalidApiKeyException
     * @throws MerchantIdChangedException
     * @throws ClientKeyGenerationFailedException
     * @throws FailedToGenerateHmacException
     * @throws UserDoesNotHaveNecessaryRolesException
     * @throws InvalidAllowedOriginException
     * @throws ApiCredentialsDoNotExistException
     * @throws InvalidConnectionSettingsException
     * @throws ModeChangedException
     * @throws ConnectionSettingsNotFoundException
     * @throws FailedToRegisterWebhookException
     * @throws PaymentMethodDataEmptyException
     */
    private function createIntegrationConfigurations(string $testApiKey): void
    {
        $createIntegrationDataService = new CreateIntegrationDataService('./custom/plugins/AdyenPayment');
        $createIntegrationDataService->createConnectionAndWebhookConfiguration($testApiKey);
        $createIntegrationDataService->createAllPaymentMethodsFromTestData();
    }

    /**
     * Get all countries and activate countries from test data
     *
     * @throws HttpRequestException
     */
    private function activateCountries(): void
    {
        $countriesTestData = array_column($this->readFromJSONFile()['countries'] ?? [], 'iso');
        $shopCountries = $this->countryTestProxy->getCountries()['data'] ?? [];

        foreach ($countriesTestData as $country) {
            $indexInArray = array_search($country, array_column($shopCountries, 'iso'), true);
            $countryId = $shopCountries[$indexInArray]['id'];

            $this->countryTestProxy->activateCountry($countryId);
        }
    }

    /**
     * @throws HttpRequestException
     */
    private function createCustomers(): void
    {
        $customersTestData = $this->readFromJSONFile()['customers'];
        foreach ($customersTestData as $customerTestData) {
            $shopCountries = $this->countryTestProxy->getCountries()['data'] ?? [];
            $indexInArray = array_search(
                $customerTestData['defaultShippingAddress']['country'],
                array_column($shopCountries, 'iso'),
                true
            );
            $countryId = $shopCountries[$indexInArray]['id'];
            $customerTestData['defaultShippingAddress']['country'] = $countryId;
            $customerTestData['defaultBillingAddress']['country'] = $countryId;

            $this->customerTestProxy->saveCustomer($customerTestData);
        }
    }

    /**
     * @return array
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function createCurrenciesInDatabase(): array
    {
        $currenciesTestData = $this->readFromJSONFile()['currencies'];
        $currencies = [];
        $manager = Shopware()->Models();

        foreach ($currenciesTestData as $currencyTestData) {
            $currency = new Currency();
            $currency->fromArray($currencyTestData);

            $manager->persist($currency);
            $manager->flush();

            $currencies[] = $currency;
        }

        return $currencies;
    }

    /**
     * @param array $currencies
     * @return void
     * @throws ORMException
     * @throws OptimisticLockException
     */
    private function addCurrenciesInSubStore(array $currencies): void
    {
        /** @var Repository $shopRepository */
        $shopRepository = Shopware()->Models()->getRepository(Shop::class);
        $shop = $shopRepository->getDefault();
        $manager = Shopware()->Models();

        if ($shop) {
            $currencies[] = $shop->getCurrency();
            $shop->setCurrencies($currencies);
            $manager->persist($shop);
            $manager->flush();
        }
    }

    /**
     * @return HttpClient
     */
    private function getHttpClient(): HttpClient
    {
        return ServiceRegister::getService(HttpClient::class);
    }
}