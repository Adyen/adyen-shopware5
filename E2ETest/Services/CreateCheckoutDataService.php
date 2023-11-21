<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use AdyenPayment\E2ETest\Http\CountryTestProxy;
use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\E2ETest\Http\CustomerTestProxy;
use AdyenPayment\E2ETest\Http\ShopsTestProxy;
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
     * @var ShopsTestProxy
     */
    private $shopProxy;

    /**
     * CreateCheckoutDataService constructor.
     *
     * @param string $credentials
     */
    public function __construct(string $credentials)
    {
        $this->countryTestProxy = new CountryTestProxy($this->getHttpClient(), 'localhost', $credentials);
        $this->customerTestProxy = new CustomerTestProxy($this->getHttpClient(), 'localhost', $credentials);
        $this->shopProxy = new ShopsTestProxy($this->getHttpClient(), 'localhost', $credentials);
    }

    /**
     * @return void
     * @throws HttpRequestException
     * @throws ORMException
     * @throws OptimisticLockException
     */
    public function crateCheckoutPrerequisitesData(): void
    {
        $this->activateCountries();
        $this->createCustomer();
        $currencies = $this->createCurrenciesInDatabase();
        $this->addCurrenciesInSubStore($currencies);
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
    private function createCustomer(): void
    {
        $customerTestData = $this->readFromJSONFile()['customer'];
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