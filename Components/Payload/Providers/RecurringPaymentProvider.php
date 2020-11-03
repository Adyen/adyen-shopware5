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
        // se-remove die(): to verify once payments are approved and recurring cards are shown in the payments list
        $storedPaymentMethodId = $paymentInfo['storedPaymentMethodId'] ?? null;
        $recurringProcessingModel = $paymentInfo['recurringProcessingModel'] ?? null; // "Subscription" or "CardOnFile"
        $storeDetails = (bool) ($paymentInfo['storeDetails'] ?? false);

        // se-remove die()
        echo '<pre>SE-DEBUG: ',print_r([
            '$storedPaymentMethodId' => $storedPaymentMethodId,
            '$recurringProcessingModel' => $recurringProcessingModel,
            '$storeDetails' => $storeDetails,
        ], true),'</pre>';die('DIE after print');

        if (!$storeDetails && !$storedPaymentMethodId) {
            return [];
        }

        // selected stored payment
        if ($storedPaymentMethodId) {
            return [
                'recurringProcessingModel' => $recurringProcessingModel,
                'shopperInteraction' => 'ContAuth',
            ];
        }

        // new payment to store
        return [
            'storePaymentMethod' => true,
            'recurringProcessingModel' => $recurringProcessingModel,
            'shopperInteraction' => 'Ecommerce',
        ];
    }
}
