<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload;

/**
 * Interface PaymentPayloadProvider
 * @package AdyenPayment\Components
 */
interface PaymentPayloadProvider
{
    /**
     * @param PaymentContext $context
     * @return array
     */
    public function provide(PaymentContext $context): array;
}
