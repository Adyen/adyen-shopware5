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
    private Configuration $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    /**
     * @return array{
     *      environment: "PRODUCTION"|"TEST",
     *      countryCode: string,
     *      amount: array{
     *          value: int,
     *          currency: string,
     *      },
     *      configuration: array{
     *          gatewayMerchantId: string
     *      },
     * }
     */
    public function __invoke(ConfigContext $context): array
    {
        return [
            'environment' => Configuration::ENV_LIVE === $this->configuration->getEnvironment() ? 'PRODUCTION' : 'TEST',
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
            ],
        ];
    }
}
