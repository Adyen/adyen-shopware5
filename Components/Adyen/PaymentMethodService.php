<?php declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Service\Checkout;
use Adyen\Util\Currency;
use AdyenPayment\Components\Configuration;
use AdyenPayment\Models\Enum\Channel;
use Psr\Log\LoggerInterface;

/**
 * Class PaymentMethodService
 * @package AdyenPayment\Components\Adyen
 */
class PaymentMethodService
{
    /**
     * @var Configuration
     */
    private $configuration;

    /**
     * @var array
     */
    private $cache;
    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ApiFactory
     */
    private $apiFactory;

    /**
     * PaymentMethodService constructor.
     * @param ApiFactory $apiFactory
     * @param Configuration $configuration
     * @throws AdyenException
     */
    public function __construct(
        ApiFactory $apiFactory,
        Configuration $configuration,
        LoggerInterface $logger
    ) {
        $this->apiFactory = $apiFactory;
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * @param string $countryCode
     * @param string $currency
     * @param int $value
     * @param null $locale
     * @param bool $cache
     * @return array
     */
    public function getPaymentMethods(
        $countryCode = null,
        $currency = null,
        $value = null,
        $locale = null,
        $cache = true
    ): array {
        $cache = $cache && $this->configuration->isPaymentmethodsCacheEnabled();
        $cacheKey = $this->getCacheKey($countryCode ?? '', $currency ?? '', (string)$value ?? '');
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
        ];

        try {
            $paymentMethods = $checkout->paymentMethods($requestParams);
        } catch (AdyenException $e) {
            $this->logger->critical('Adyen Exception', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'errorType' => $e->getErrorType(),
                'status' => $e->getStatus()
            ]);
            return [];
        }

        if ($cache) {
            $this->cache[$cacheKey] = $paymentMethods;
        }

        return $paymentMethods;
    }

    /**
     * @param string ...$keys
     * @return string
     */
    private function getCacheKey(string ...$keys)
    {
        return md5(implode(',', $keys));
    }

    /**
     * @throws AdyenException
     */
    public function getCheckout(): Checkout
    {
        $apiClient = $this->apiFactory->provide(Shopware()->Shop());

        return new Checkout($apiClient);
    }
}
