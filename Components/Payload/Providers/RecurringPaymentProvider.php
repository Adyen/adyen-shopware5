<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

class RecurringPaymentProvider implements PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array
    {
        $paymentInfo = $context->getPaymentInfo();
        $storedPaymentMethodId = $paymentInfo['storedPaymentMethodId'] ?? null;
        if (!$storedPaymentMethodId) {
            return [];
        }

        return [
            'shopperInteraction' => 'ContAuth',
            'recurringProcessingModel' => 'CardOnFile',
        ];
    }
}
