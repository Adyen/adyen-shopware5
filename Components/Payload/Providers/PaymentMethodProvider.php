<?php
declare(strict_types=1);

namespace MeteorAdyen\Components\Payload\Providers;

use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\PaymentPayloadProvider;

/**
 * Class PaymentMethodProvider
 * @package MeteorAdyen\Components\Payload\Providers
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
