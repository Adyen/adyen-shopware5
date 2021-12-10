<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Service\Checkout;
use Adyen\Util\Currency;
use AdyenPayment\Collection\Payment\PaymentMethodCollection;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Models\Enum\Channel;
use Enlight_Components_Session_Namespace;
use Psr\Log\LoggerInterface;
use Shopware\Components\Model\ModelManager;
use Shopware\Models\Customer\Customer;

final class PaymentMethodService
{
    private ApiClientMap $apiClientMap;
    private Configuration $configuration;
    private array $cache;
    private LoggerInterface $logger;
    private Enlight_Components_Session_Namespace $session;
    private ModelManager $modelManager;

    public function __construct(
        ApiClientMap $apiClientMap,
        Configuration $configuration,
        LoggerInterface $logger,
        Enlight_Components_Session_Namespace $session,
        ModelManager $modelManager
    ) {
        $this->apiClientMap = $apiClientMap;
        $this->configuration = $configuration;
        $this->logger = $logger;
        $this->session = $session;
        $this->modelManager = $modelManager;
    }

    /**
     * @throws AdyenException
     */
    public function getPaymentMethods(
        ?string $countryCode = null,
        ?string $currency = null,
        ?float $value = null,
        ?string $locale = null,
        bool $cache = true
    ): PaymentMethodCollection {
        $cache = $cache && $this->configuration->isPaymentmethodsCacheEnabled();
        $cacheKey = $this->getCacheKey($countryCode ?? '', $currency ?? '', (string) ($value ?? ''));
        if ($cache && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $checkout = $this->getCheckout();
        $adyenCurrency = new Currency();

        $requestParams = [
            'merchantAccount' => $this->configuration->getMerchantAccount(),
            'countryCode' => $countryCode,
            'amount' => [
                'currency' => $currency,
                'value' => $adyenCurrency->sanitize($value, $currency),
            ],
            'channel' => Channel::WEB,
            'shopperLocale' => $locale ?? Shopware()->Shop()->getLocale()->getLocale(),
            'shopperReference' => $this->provideCustomerNumber(),
        ];

        try {
            $paymentMethods = PaymentMethodCollection::fromAdyenMethods(
                $checkout->paymentMethods($requestParams)
            );
        } catch (AdyenException $e) {
            $this->logger->critical('Adyen Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'errorType' => $e->getErrorType(),
                'status' => $e->getStatus(),
            ]);

            return new PaymentMethodCollection();
        }

        if ($cache) {
            $this->cache[$cacheKey] = $paymentMethods;
        }

        return $paymentMethods;
    }

    private function getCacheKey(string ...$keys): string
    {
        return md5(implode(',', $keys));
    }

    /**
     * @throws AdyenException
     */
    public function getCheckout(): Checkout
    {
        return new Checkout(
            $this->apiClientMap->lookup(
                Shopware()->Shop()
            )
        );
    }

    private function provideCustomerNumber(): string
    {
        $userId = $this->session->get('sUserId');
        if (!$userId) {
            return '';
        }
        $customer = $this->modelManager->getRepository(Customer::class)->find($userId);

        return $customer ? (string) $customer->getNumber() : '';
    }
}
