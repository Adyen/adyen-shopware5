<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\Connection\Request\ConnectionRequest;
use Adyen\Core\BusinessLogic\AdminAPI\Payment\Request\PaymentMethodRequest;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\DataBag;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Models\CaptureType;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionHistoryService;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use AdyenPayment\E2ETest\Http\CountryTestProxy;
use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\E2ETest\Http\OrderTestProxy;
use Doctrine\ORM\OptimisticLockException;
use Doctrine\ORM\ORMException;
use Shopware\Models\Shop\Repository;
use Shopware\Models\Shop\Shop;
use Shopware\Models\User\User;

/**
 * Class CreateCheckoutDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class CreateOrderDataService extends BaseCreateSeedDataService
{
    /**
     * @var CountryTestProxy
     */
    private $orderTestProxy;

    /**
     * CreateCheckoutDataService constructor.
     *
     * @param string $credentials
     */
    public function __construct(string $credentials)
    {
        $this->orderTestProxy = new OrderTestProxy($this->getHttpClient(), 'localhost', $credentials);
    }

    /**
     * @return void
     * @throws HttpRequestException
     * @throws \Exception
     */
    public function crateOrderPrerequisitesData(): void
    {
        $this->createPluginConfigurations();
        $this->createOrder();
        StoreContext::doWithStore('1', function () {
            $transactionContext = new StartTransactionRequestContext(
                PaymentMethodCode::parse('scheme'),
                Amount::fromFloat(
                    15,
                    Currency::fromIsoCode('EUR')
                ),
                '79b8eede513674f3e4ff909ff23btest',
                '',
                new DataBag([]),
                new DataBag([])
            );
            /** @var TransactionHistoryService $transactionHistoryService */
            $transactionHistoryService = ServiceRegister::getService(TransactionHistoryService::class);
            $transactionHistoryService->createTransactionHistory($transactionContext->getReference(),
                $transactionContext->getAmount()->getCurrency(),
                CaptureType::manual()); //read from configuration
        });
    }

    private function createPluginConfigurations(): void
    {
        $connectionRequest = new ConnectionRequest(
            1,
            'test',
            'AQEqhmfxLo7MbhxFw0m/n3Q5qf3VZIRKCJZJV2iaro8WVf7y1+LULDB4XAoIEMFdWw2+5HzctViMSCJMYAc=-Xp0CcQH3mmESzJ4xWXP/HUwteV+UjW09RsJVkgfVFvI=-C#7)N={N9rC>3caV',
            '',
            '',
            ''
        );

        $result = AdminAPI::get()->connection(1)->connect($connectionRequest);
        $connectionRequest = new ConnectionRequest(
            1,
            'test',
            'AQEqhmfxLo7MbhxFw0m/n3Q5qf3VZIRKCJZJV2iaro8WVf7y1+LULDB4XAoIEMFdWw2+5HzctViMSCJMYAc=-Xp0CcQH3mmESzJ4xWXP/HUwteV+UjW09RsJVkgfVFvI=-C#7)N={N9rC>3caV',
            'LogeecomECOM',
            '',
            ''
        );

        $result = AdminAPI::get()->connection(1)->connect($connectionRequest);

        $method = PaymentMethodRequest::parse($this->readFromJSONFile()['creditCartPaymentMethod'] ?? []);
        $result = AdminAPI::get()->payment(1)->saveMethodConfiguration($method);
    }

    /**
     * Get all countries and activate countries from test data
     *
     * @throws HttpRequestException
     */
    private function createOrder(): void
    {
        $orderTestData = $this->readFromJSONFile()['order'] ?? [];
        $this->orderTestProxy->createOrder($orderTestData);
    }

    /**
     * @return HttpClient
     */
    private function getHttpClient(): HttpClient
    {
        return ServiceRegister::getService(HttpClient::class);
    }
}