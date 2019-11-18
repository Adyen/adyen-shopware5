<?php
declare(strict_types=1);

namespace MeteorAdyen\Components\Payload\Providers;

use Adyen\Util\Currency;
use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\PaymentPayloadProvider;

/**
 * Class OrderInfoProvider
 * @package MeteorAdyen\Components\Payload\Providers
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