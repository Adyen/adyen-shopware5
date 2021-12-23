<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload;

/**
 * Interface PaymentPayloadProvider.
 */
interface PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array;
}
