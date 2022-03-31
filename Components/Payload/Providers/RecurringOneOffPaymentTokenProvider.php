<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;
use AdyenPayment\Models\RecurringPayment\RecurringProcessingModel;
use AdyenPayment\Models\RecurringPayment\ShopperInteraction;

final class RecurringOneOffPaymentTokenProvider implements PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array
    {
        $paymentInfo = $context->getPaymentInfo();
        $storedPaymentMethodId = (string) ($paymentInfo['storedPaymentMethodId'] ?? '');
        if ('' === $storedPaymentMethodId) {
            return [];
        }

        return [
            'shopperInteraction' => ShopperInteraction::ecommerce()->shopperInteraction(),
            'recurringProcessingModel' => RecurringProcessingModel::cardOnFile()->recurringProcessingModel(),
        ];
    }
}
