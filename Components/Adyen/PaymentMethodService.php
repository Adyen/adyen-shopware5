<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Client;
use Adyen\Service\Checkout;
use MeteorAdyen\Components\Configuration;

/**
 * Class PaymentMethodService
 * @package MeteorAdyen\Components\Adyen
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
     * PaymentMethodService constructor.
     * @param ApiFactory $apiFactory
     * @param Configuration $configuration
     * @throws AdyenException
     */
    public function __construct(
        ApiFactory $apiFactory,
        Configuration $configuration
    ) {
        $this->apiClient = $apiFactory->create();
        $this->configuration = $configuration;
    }

    /**
     * @param string $countryCode
     * @param string $currency
     * @param int $value
     * @param bool $cache
     * @return array
     * @throws AdyenException
     */
    public function getPaymentMethods($countryCode = "BE", $currency = "EUR", $value = 100, $cache = true): array
    {
        $checkout = new Checkout($this->apiClient);

        $cacheKey = $this->getCacheKey($countryCode, $currency, (string)$value);
        if ($cache && isset($this->cache[$cacheKey])) {
            return $this->cache[$cacheKey];
        }

        $paymentMethods = $checkout->paymentMethods([
            "merchantAccount" => $this->configuration->getMerchantAccount(),
            "countryCode" => $countryCode,
            "amount" => [
                "currency" => $currency,
                "value" => $value
            ],
            "channel" => "Web"
        ]);

        if ($cache) {
            $this->cache[$cacheKey] = $paymentMethods;
        }
        return $paymentMethods;
    }

    /**
     * @param string ...$keys
     * @return string
     */
    private function getCacheKey(string ...$keys) {
        return md5(implode(',', $keys));
    }
}