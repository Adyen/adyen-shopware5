<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

/** @TODO - Pending unit tests */
final class RecurringPaymentProvider implements PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array
    {
        $paymentInfo = $context->getPaymentInfo();
        $storedPaymentMethodId = (string) ($paymentInfo['storedPaymentMethodId'] ?? '');
        if ('' === $storedPaymentMethodId) {
            return [];
        }

        return [
            'shopperInteraction' => 'ContAuth',
            'recurringProcessingModel' => 'CardOnFile',
        ];
    }
}
