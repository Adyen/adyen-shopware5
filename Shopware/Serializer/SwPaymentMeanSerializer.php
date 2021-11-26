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
                'name' => $this->escape($paymentMean->getValue('name')),
                'description' => $this->escape($paymentMean->getValue('description')),
                'additionaldescription' => $this->escape($paymentMean->getValue('additionaldescription')),
            ]),
        ];
    }

    private function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_NOQUOTES);
    }
}
