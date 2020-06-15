<?php

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

/**
 * Class PaymentMethodProvider
 * @package AdyenPayment\Components\Payload\Providers
 */
class PaymentMethodProvider implements PaymentPayloadProvider
{
    /**
     * @param PaymentContext $context
     * @return array
     */
    public function provide(PaymentContext $context): array
    {
        return [
            'paymentMethod' => $context->getPaymentInfo(),
        ];
    }
}
