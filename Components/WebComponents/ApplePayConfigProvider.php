<?php

declare(strict_types=1);

namespace AdyenPayment\Components\WebComponents;

use Adyen\Util\Currency;

/**
 * @see https://docs.adyen.com/payment-methods/apple-pay/web-component
 */
class ApplePayConfigProvider implements ConfigProvider
{
    /**
     * @return array{
     *      countryCode: string,
     *      amount: array{
     *          value: int,
     *          currency: string,
     *      },
     *      configuration: array{
     *          merchantName: string,
     *          merchantId: string,
     *      }
     * }
     */
    public function __invoke(ConfigContext $context): array
    {
        $paymentData = $context->getUserData()['additional']['payment'];

        if (!isset($paymentData['metadata']['configuration'])) {
            $configuration = [];
        }

        $configuration['merchantName'] = $paymentData['metadata']['configuration']['merchantName'] ?? '';
        $configuration['merchantId'] = $paymentData['metadata']['configuration']['merchantId'] ?? '';

        return [
            'countryCode' => (string) ($context->getUserData()['additional']['country']['countryiso'] ?? ''),
            'amount' => [
                'value' => (new Currency())->sanitize(
                    (float) ($context->getBasket()['AmountNumeric'] ?? 0.0),
                    (string) ($context->getBasket()['sCurrencyName'] ?? '')
                ),
                'currency' => (string) ($context->getBasket()['sCurrencyName'] ?? ''),
            ],
            'configuration' => $configuration,
        ];
    }
}
