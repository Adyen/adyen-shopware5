<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

/**
 * Class PaymentMethodProvider.
 */
class PaymentMethodProvider implements PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array
    {
        return [
            'paymentMethod' => $context->getPaymentInfo(),
        ];
    }
}
