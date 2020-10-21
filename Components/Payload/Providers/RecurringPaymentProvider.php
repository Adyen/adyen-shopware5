<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Adyen\Model\RecurringProcessing;
use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

class RecurringPaymentProvider implements PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array
    {
        $paymentInfo = $context->getPaymentInfo();
        $storedPaymentMethodId = $paymentInfo['storedPaymentMethodId'] ?? null;
        $storeDetails = (bool) ($paymentInfo['storeDetails'] ?? false);

        if (!$storeDetails && !$storedPaymentMethodId) {
            return [];
        }

        // se-remove die(): update this
        // selected stored payment
        if ($storedPaymentMethodId) {
            return [
                'recurringProcessingModel' => $paymentInfo[''], // @todo get from data "Subscription" or "CardOnFile"
                'shopperInteraction' => 'ContAuth',
            ];
        }

        // new payment to store
        return [
            'storePaymentMethod' => true,
            'recurringProcessingModel' => $context->getPaymentInfo()[''], // @todo  "Subscription" or "CardOnFile"
            'shopperInteraction' => 'Ecommerce',
        ];
    }
}
