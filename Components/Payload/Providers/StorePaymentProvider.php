<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

class StorePaymentProvider implements PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array
    {
        return [
            'storePaymentMethod' => $context->enableStorePaymentMethod(),
        ];
    }
}
