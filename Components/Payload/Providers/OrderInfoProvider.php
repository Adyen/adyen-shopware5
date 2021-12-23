<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use Adyen\Util\Currency;
use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

/**
 * Class OrderInfoProvider.
 */
class OrderInfoProvider implements PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array
    {
        $adyenCurrency = new Currency();
        $currencyCode = $context->getOrder()->getCurrency();

        return [
            'amount' => [
                'currency' => $currencyCode,
                'value' => $adyenCurrency->sanitize($context->getOrder()->getInvoiceAmount(), $currencyCode),
            ],
            'reference' => $context->getOrder()->getNumber(),
        ];
    }
}
