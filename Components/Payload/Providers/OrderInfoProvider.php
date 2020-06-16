<?php

namespace AdyenPayment\Components\Payload\Providers;

use Adyen\Util\Currency;
use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

/**
 * Class OrderInfoProvider
 * @package AdyenPayment\Components\Payload\Providers
 */
class OrderInfoProvider implements PaymentPayloadProvider
{
    /**
     * @param PaymentContext $context
     * @return array
     */
    public function provide(PaymentContext $context): array
    {
        $adyenCurrency = new Currency();
        $currencyCode = $context->getOrder()->getCurrency();

        return [
            'amount' => [
                "currency" => $currencyCode,
                "value" => $adyenCurrency->sanitize($context->getOrder()->getInvoiceAmount(), $currencyCode),
            ],
            'reference' => $context->getOrder()->getNumber(),
        ];
    }
}
