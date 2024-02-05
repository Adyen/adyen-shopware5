<?php

namespace AdyenPayment\E2ETest\Services;

use Adyen\Core\BusinessLogic\AdminAPI\AdminAPI;
use Adyen\Core\BusinessLogic\AdminAPI\OrderMappings\Request\OrderMappingsRequest;
use Adyen\Core\BusinessLogic\Domain\GeneralSettings\Models\CaptureType;
use Adyen\Core\BusinessLogic\E2ETest\Services\CreateIntegrationDataService;
use Adyen\Core\Infrastructure\Http\Exceptions\HttpRequestException;
use AdyenPayment\E2ETest\Http\CountryTestProxy;
use Adyen\Core\Infrastructure\ServiceRegister;
use AdyenPayment\E2ETest\Http\OrderTestProxy;
use AdyenPayment\E2ETest\Http\PaymentMethodsTestProxy;
use AdyenPayment\E2ETest\Http\UserTestProxy;
use AdyenPayment\E2ETest\Repositories\ArticleRepository;
use Exception;
use Shopware\Components\Api\Exception\NotFoundException;
use Shopware\Components\Api\Exception\ParameterMissingException;
use Shopware\Models\Order\Status;

/**
 * Class CreateWebhooksDataService
 *
 * @package AdyenPayment\E2ETest\Services
 */
class CreateWebhooksDataService extends BaseCreateSeedDataService
{
    /**
     * @var ArticleRepository
     */
    private $articleRepository;

    /**
     * CreateCheckoutDataService constructor.
     *
     */
    public function __construct()
    {
        $this->articleRepository = new ArticleRepository();
    }

    /**
     * @return array
     * @throws HttpRequestException
     * @throws Exception
     */
    public function getWebhookAuthorizationData(): array
    {
        return $this->getCreateIntegrationDataService()->getWebhookAuthorizationData();
    }

    /**
     * @return void
     * @throws HttpRequestException
     * @throws Exception
     */
    public function crateOrderPrerequisitesData(int $customerId): array
    {
        $this->createAdminUsers();
        $this->getCreateIntegrationDataService()->createGeneralSettingsConfiguration();
        $this->createOrdersMappingConfiguration();
        return $this->createOrders($customerId);
    }

    /**
     * @throws HttpRequestException
     */
    private function createAdminUsers(): void
    {
        $usersData = $this->readFromJSONFile()['users'] ?? [];
        foreach ($usersData as $userData) {
            $this->getUserTestProxy()->createUser($userData);
        }
    }

    private function createOrdersMappingConfiguration(): void
    {
        $ordersMappingConfigurationData = AdminAPI::get()->orderMappings(1)->getOrderStatusMap()->toArray();
        $ordersMappingConfigurationData[ 'refunded'] = Status::PAYMENT_STATE_1ST_REMINDER;
        $ordersMappingConfigurationData[ 'partiallyRefunded'] = Status::PAYMENT_STATE_2ND_REMINDER;
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
        $orders = $this->readFromJSONFile()['orders'] ?? [];
        $customerData = $this->readFromJSONFile()['customer'] ?? [];
        $currencies = $this->readFromJSONFile()['currencies'] ?? [];
        foreach ($orders as $order) {
            $captureType = $this->getCaptureType($order['captureType']);
            unset($order['captureType']);
            $orderName = $order['name'];
            unset($order['name']);
            $totalAmount = $this->createOrder($order, $customerId, $customerData, $currencies);
            $this->getCreateIntegrationDataService()->createTransactionHistoryForOrder(
                $order['temporaryId'],
                $totalAmount,
                $order['currency'],
                $captureType
            );
            $ordersMerchantReferenceAndAmount[$orderName] = [
                'merchantReference' => $order['temporaryId'],
                'amount' => $totalAmount * 100
            ];
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
        $shopCountries = $this->getCountryTestProxy()->getCountries()['data'] ?? [];
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
            $totalAmount += round(round($order["details"][$i]["price"], 2) * $order["details"][$i]['quantity'] * $order["currencyFactor"], 2);
            $totalNetAmount += round(round($order["details"][$i]["price"], 2) * (100 + (float)$order["details"][$i]["taxRate"]) / 100
                * $order["details"][$i]['quantity'] * $order["currencyFactor"], 2);
        }

        $order["invoiceAmount"] = $totalAmount;
        $order["invoiceAmountNet"] = $totalNetAmount;

        $this->getOrderTestProxy()->createOrder($order);

        return $totalAmount;
    }

    /**
     * @return int
     * @throws HttpRequestException
     */
    private function getPaymentMethodId(): int
    {
        $paymentMethods = $this->getPaymentMethodsTestProxy()->getPaymentMethods()['data'] ?? [];
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
     * @return OrderTestProxy
     */
    private function getOrderTestProxy(): OrderTestProxy
    {
        return ServiceRegister::getService(OrderTestProxy::class);
    }

    /**
     * @return CountryTestProxy
     */
    private function getCountryTestProxy(): CountryTestProxy
    {
        return ServiceRegister::getService(CountryTestProxy::class);
    }

    /**
     * @return PaymentMethodsTestProxy
     */
    private function getPaymentMethodsTestProxy(): PaymentMethodsTestProxy
    {
        return ServiceRegister::getService(PaymentMethodsTestProxy::class);
    }

    /**
     * @return UserTestProxy
     */
    private function getUserTestProxy(): UserTestProxy
    {
        return ServiceRegister::getService(UserTestProxy::class);
    }

    /**
     * Returns CreateIntegrationDataService instance
     *
     * @return CreateIntegrationDataService
     */
    private function getCreateIntegrationDataService(): CreateIntegrationDataService
    {
        return ServiceRegister::getService(CreateIntegrationDataService::class);
    }
}