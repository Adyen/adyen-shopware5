<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\OrderMappings\Request\OrderMappingsRequest;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Amount;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\Amount\Currency;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\DataBag;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\PaymentMethodCode;
use Adyen\Core\BusinessLogic\Domain\Checkout\PaymentRequest\Models\StartTransactionRequestContext;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Models\CaptureType;
use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\BusinessLogic\Domain\TransactionHistory\Services\TransactionHistoryService;
use Adyen\Core\BusinessLogic\Domain\Webhook\Repositories\WebhookConfigRepository;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use AdyenPayment\E2ETest\Http\CountryTestProxy;
use Adyen\Core\Infrastructure\Http\HttpClient;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\E2ETest\Http\OrderTestProxy;
use AdyenPayment\E2ETest\Http\PaymentMethodsTestProxy;
use AdyenPayment\E2ETest\Repositories\ArticleRepository;
use Exception;
use Shopware\Components\Api\Exception\NotFoundException;
use Shopware\Components\Api\Exception\ParameterMissingException;

/**
 * Class CreateWebhooksDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class CreateWebhooksDataService extends BaseCreateSeedDataService
{
    /**
     * @var CountryTestProxy
     */
    private $orderTestProxy;
    /**
     * @var CountryTestProxy
     */
    private $countryTestProxy;
    /**
     * @var PaymentMethodsTestProxy
     */
    private $paymentMethodsTestProxy;
    /**
     * @var ArticleRepository
     */
    private $articleRepository;

    /**
     * CreateCheckoutDataService constructor.
     *
     * @param string $credentials
     */
    public function __construct(string $credentials)
    {
        $this->orderTestProxy = new OrderTestProxy($this->getHttpClient(), 'localhost', $credentials);
        $this->countryTestProxy = new CountryTestProxy($this->getHttpClient(), 'localhost', $credentials);
        $this->paymentMethodsTestProxy = new PaymentMethodsTestProxy($this->getHttpClient(), 'localhost', $credentials);
        $this->articleRepository = new ArticleRepository();
    }

    /**
     * @throws Exception
     */
    public function getWebhookAuthorizationData(): array
    {
        $webhookConfig = StoreContext::doWithStore(1, function () {
            return $this->getWebhookConfigRepository()->getWebhookConfig();
        });

        $authData = [];
        if ($webhookConfig) {
            $authData['username'] = $webhookConfig->getUsername();
            $authData['password'] = $webhookConfig->getPassword();
            $authData['hmac'] = $webhookConfig->getHmac();
        }

        return $authData;
    }

    /**
     * @return void
     * @throws HttpRequestException
     * @throws \Exception
     */
    public function crateOrderPrerequisitesData(int $customerId): array
    {
        $this->createOrdersMappingConfiguration();
        return $this->createOrders($customerId);
    }

    private function createOrdersMappingConfiguration(): void
    {
        $ordersMappingConfigurationData = $this->readFromJSONFile()['ordersMappingConfiguration'] ?? [];
        $orderStatusMapRequest = OrderMappingsRequest::parse($ordersMappingConfigurationData);

        AdminAPI::get()->orderMappings(1)->saveOrderStatusMap($orderStatusMapRequest);
    }


    /**
     * Get all countries and activate countries from test data
     *
     * @param int $customerId
     * @return array
     * @throws HttpRequestException
     * @throws NotFoundException
     * @throws ParameterMissingException
     * @throws Exception
     */
    private function createOrders(int $customerId): array
    {
        $ordersMerchantReferenceAndAmount = [];
        $index = 1;
        $orders = $this->readFromJSONFile()['orders'] ?? [];
        $customerData = $this->readFromJSONFile()['customer'] ?? [];
        $currencies = $this->readFromJSONFile()['currencies'] ?? [];
        foreach ($orders as $order) {
            $captureType = $this->getCaptureType($order['captureType']);
            unset($order['captureType']);
            $totalAmount = $this->createOrder($order, $customerId, $customerData, $currencies);
            StoreContext::doWithStore('1', static function () use ($captureType, $totalAmount, $order) {
                $transactionContext = new StartTransactionRequestContext(
                    PaymentMethodCode::parse('scheme'),
                    Amount::fromFloat(
                        $totalAmount,
                        Currency::fromIsoCode(
                            $order['currency']
                        )
                    ),
                    $order['temporaryId'],
                    '',
                    new DataBag([]),
                    new DataBag([])
                );
                /** @var TransactionHistoryService $transactionHistoryService */
                $transactionHistoryService = ServiceRegister::getService(TransactionHistoryService::class);
                $transactionHistoryService->createTransactionHistory($transactionContext->getReference(),
                    $transactionContext->getAmount()->getCurrency(),
                    $captureType
                );
            });

            $ordersMerchantReferenceAndAmount['order_' . $index] = [
                'merchantReference' => $order['temporaryId'],
                'amount' => $totalAmount * 100
            ];
            $index++;
        }

        return $ordersMerchantReferenceAndAmount;
    }

    /**
     * @param array $order
     * @param int $customerId
     * @return void
     * @throws HttpRequestException
     * @throws NotFoundException
     * @throws ParameterMissingException
     */
    private function createOrder(array $order, int $customerId, array $customerData, array $currencies): float
    {
        $order["customerId"] = $customerId;
        $order["shipping"] = $customerData['defaultShippingAddress'];
        $order["billing"] = $customerData['defaultBillingAddress'];
        $order["billing"]["customerId"] = $customerId;
        $order["shipping"]["customerId"] = $customerId;
        $shopCountries = $this->countryTestProxy->getCountries()['data'] ?? [];
        $indexInArray = array_search(
            $order["shipping"]['country'],
            array_column($shopCountries, 'iso'),
            true
        );
        $countryId = $shopCountries[$indexInArray]['id'];
        $order["shipping"]['countryId'] = $countryId;
        $order["billing"]['countryId'] = $countryId;
        $order["paymentId"] = $this->getPaymentMethodId();
        $order["paymentStatusId"] = AdminAPI::get()->orderMappings(1)->getOrderStatusMap()->toArray()['inProgress'];
        $indexInArray = array_search(
            $order["currency"],
            array_column($currencies, 'currency'),
            true
        );
        $order["currencyFactor"] = $currencies[$indexInArray]['factor'];
        $totalAmount = 0;
        $totalNetAmount = 0;
        $detailsCount = count($order["details"]);
        for ($i = 0; $i < $detailsCount; $i++) {
            $article = $this->articleRepository->getShopwareArticle($order["details"][$i]['articleId']);
            $order["details"][$i]["articleName"] = $article->getName();
            $order["details"][$i]["taxId"] = $article->getTax() ? $article->getTax()->getId() : -1;
            $order["details"][$i]["taxRate"] = $article->getTax() ? $article->getTax()->getTax() : '';
            $mainDetail = $article->getMainDetail();
            $order["details"][$i]["articleDetailId"] = $mainDetail ? $mainDetail->getId() : -1;
            $order["details"][$i]["articleNumber"] = $mainDetail ? $mainDetail->getNumber() : -1;
            $price = $this->articleRepository->getShopwareArticleDetailsPrices($mainDetail->getId());
            $order["details"][$i]["price"] = $price->getPrice();
            $totalAmount += round(round($order["details"][$i]["price"], 2) * $order["details"][$i]['quantity'], 2);
            $totalNetAmount += round(round($order["details"][$i]["price"], 2) * (100 + (float)$order["details"][$i]["taxRate"]) / 100
                * $order["details"][$i]['quantity'], 2);
        }

        $order["invoiceAmount"] = $totalAmount;
        $order["invoiceAmountNet"] = $totalNetAmount;

        $this->orderTestProxy->createOrder($order);

        return $totalAmount;
    }

    /**
     * @return int
     * @throws HttpRequestException
     */
    private function getPaymentMethodId(): int
    {
        $paymentMethods = $this->paymentMethodsTestProxy->getPaymentMethods()['data'] ?? [];
        $indexInArray = array_search('adyen_scheme', array_column($paymentMethods, 'name'), true);

        return $paymentMethods[$indexInArray]['id'];
    }

    private function getCaptureType(string $captureTypeData): CaptureType
    {
        if ($captureTypeData === 'manual') {
            return CaptureType::manual();
        }

        if ($captureTypeData === 'immediate') {
            return CaptureType::immediate();
        }

        return CaptureType::delayed();
    }

    /**
     * @return HttpClient
     */
    private function getHttpClient(): HttpClient
    {
        return ServiceRegister::getService(HttpClient::class);
    }

    /**
     * Returns WebhookConfigRepository instance
     *
     * @return WebhookConfigRepository
     */
    private function getWebhookConfigRepository(): WebhookConfigRepository
    {
        return ServiceRegister::getService(WebhookConfigRepository::class);
    }
}