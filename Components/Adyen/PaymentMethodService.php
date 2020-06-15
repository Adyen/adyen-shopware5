<?php declare(strict_types=1);

namespace AdyenPayment\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Client;
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
     * @var Client
     */
    private $apiClient;

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
        $this->apiClient = $apiFactory->create();
        $this->configuration = $configuration;
        $this->logger = $logger;
    }

    /**
     * @param string $countryCode
     * @param string $currency
     * @param int $value
     * @param bool $cache
     * @return array
     * @throws AdyenException
     */
    public function getPaymentMethods(
        $countryCode = null,
        $currency = null,
        $locale = null,
        $value = null,
        $cache = true
    ): array {
        $cacheKey = $this->getCacheKey($countryCode ?? '', $currency ?? '', (string)$value ?? '');
        if ($cache && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $checkout = new Checkout($this->apiClient);
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
            $this->logger->critical($e);
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
     * @return Checkout
     * @throws AdyenException
     */
    public function getCheckout()
    {
        return new Checkout($this->apiClient);
    }
}
