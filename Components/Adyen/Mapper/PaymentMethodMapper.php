<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Adyen\Mapper;

use AdyenPayment\Models\Payment\PaymentMethod;

class PaymentMethodMapper implements PaymentMethodMapperInterface
{
    public function mapFromAdyen(array $data): \Generator
    {
        $paymentMethods = $data['paymentMethods'] ?? [];
        if (count($paymentMethods) > 0) {
            foreach ($paymentMethods as $paymentMethod) {
                yield PaymentMethod::fromRawPaymentData($paymentMethod);
            }
        }

        $storedPaymentMethods = $data['storedPaymentMethods'] ?? [];
        if (count($storedPaymentMethods)) {
            foreach ($storedPaymentMethods as $storedPaymentMethod) {
                yield PaymentMethod::fromRawPaymentData($storedPaymentMethod);
            }
        }
    }
}
