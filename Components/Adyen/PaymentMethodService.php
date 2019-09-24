<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\Adyen;

use Adyen\AdyenException;
use Adyen\Service\Checkout;
use MeteorAdyen\Components\Configuration;

/**
 * Class PaymentMethodService
 * @package MeteorAdyen\Components\Adyen
 */
class PaymentMethodService
{
    /**
     * @var ApiFactory
     */
    protected $apiFactory;

    /**
     * @var \Adyen\Client
     */
    protected $apiClient;

    /**
     * @var ConfigurationService
     */
    protected $configuration;

    /**
     * PaymentMethodService constructor.
     * @param ApiFactory $apiFactory
     * @param Configuration $configuration
     * @throws AdyenException
     */
    public function __construct(
        ApiFactory $apiFactory,
        Configuration $configuration
    )
    {
        $this->apiFactory = $apiFactory;
        $this->apiClient = $apiFactory->create();
        $this->configuration = $configuration;
    }

    /**
     * @param string $countryCode
     * @param string $currency
     * @param int $value
     * @return array
     * @throws AdyenException
     */
    public function getPaymentMethods($countryCode = "BE", $currency = "EUR", $value = 100): array
    {
        $checkout = new Checkout($this->apiClient);

        return $checkout->paymentMethods([
            "merchantAccount" => $this->configuration->getMerchantAccount(),
            "countryCode" => $countryCode,
            "amount" => [
                "currency" => $currency,
                "value" => $value
            ],
            "channel" => "Web"
        ]);
    }
}