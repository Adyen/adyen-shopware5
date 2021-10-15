<?php

declare(strict_types=1);

namespace AdyenPayment\Components\WebComponents;

use Adyen\Util\Currency;
use AdyenPayment\Components\Configuration;

/**
 * @see https://docs.adyen.com/payment-methods/google-pay/web-component
 */
final class GooglePayConfigProvider implements ConfigProvider
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function __invoke(ConfigContext $context): array
    {
        return [
            'environment' => $this->configuration->getEnvironment() === Configuration::ENV_LIVE ? 'PRODUCTION' : 'TEST',
            'countryCode' => (string) ($context->getUserData()['additional']['country']['countryiso'] ?? ''),
            'amount' => [
                'value' => (new Currency())->sanitize(
                    (float) ($context->getBasket()['AmountNumeric'] ?? 0.0),
                    (string) ($context->getBasket()['sCurrencyName'] ?? '')
                ),
                'currency' => (string) ($context->getBasket()['sCurrencyName'] ?? ''),
            ],
            'configuration' => [
                'gatewayMerchantId' => $this->configuration->getMerchantAccount(),
                'merchantId' => $this->configuration->getGoogleMerchantId(),
            ]
        ];
    }
}
