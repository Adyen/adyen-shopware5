<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Serializer;

use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Serializer\PaymentMeanSerializer;
use AdyenPayment\Utils\Sanitize;

final class SwPaymentMeanSerializer implements PaymentMeanSerializer
{
    public function __invoke(PaymentMean $paymentMean): array
    {
        return [
            $paymentMean->getId() => array_replace($paymentMean->getRaw(), [
                'name' => Sanitize::escape($paymentMean->getValue('name')),
                'description' => Sanitize::escape($paymentMean->getValue('description')),
                'additionaldescription' => Sanitize::escapeWithQuotes($paymentMean->getValue('additionaldescription')),
            ]),
        ];
    }
}
