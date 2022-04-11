<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Serializer;

use AdyenPayment\Models\Payment\PaymentMean;
use AdyenPayment\Serializer\PaymentMeanSerializer;

final class SwPaymentMeanSerializer implements PaymentMeanSerializer
{
    public function __invoke(PaymentMean $paymentMean): array
    {
        return [
            $paymentMean->getId() => array_replace($paymentMean->getRaw(), [
                'name' => $paymentMean->getValue('name'),
                'description' => $paymentMean->getValue('description'),
                'additionaldescription' => $paymentMean->getValue('additionaldescription'),
            ]),
        ];
    }
}
