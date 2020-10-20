<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

class RecurringPaymentProvider implements PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array
    {
        // se-remove die(): verify "storeDetails" coming from frontend
        $storeDetails = (bool) ($context->getPaymentInfo()['storeDetails'] ?? false);
        if (!$storeDetails) {
            return [];
        }

        return [
            'storePaymentMethod' => true,
            'recurringProcessingModel' => '', // "Subscription" or "CardOnFile"
            'shopperInteraction' => '', // "Ecommerce" on initial payment or "ContAuth" on recurring or "Moto" on
        ];
    }
}
