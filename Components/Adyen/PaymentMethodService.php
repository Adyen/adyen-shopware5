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

final class PaymentMethodService implements PaymentMethodServiceInterface
{
    /** @todo cleanup the public const (unify the services) */
    public const IMPORT_LOCALE = 'en_GB';
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

        $locale = $locale ?: Shopware()->Shop()->getLocale()->getLocale();

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
            'shopperLocale' => $locale,
            'shopperReference' => $this->provideCustomerNumber(),
        ];

        try {
            $paymentMethods = PaymentMethodCollection::fromAdyenMethods(
                $checkout->paymentMethods($requestParams)
            );

            // get payment methods import locale (important for code)
            $paymentMethods = self::IMPORT_LOCALE === $locale
                ? $paymentMethods->withImportLocale($paymentMethods)
                : $paymentMethods->withImportLocale(
                    PaymentMethodCollection::fromAdyenMethods($checkout->paymentMethods(
                        array_replace($requestParams, ['shopperLocale' => self::IMPORT_LOCALE])
                    ))
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
