<?php
declare(strict_types=1);

namespace MeteorAdyen\Components\Payload;

/**
 * Interface PaymentPayloadProvider
 * @package MeteorAdyen\Components
 */
interface PaymentPayloadProvider
{
    /**
     * @param PaymentContext $context
     * @return array
     */
    public function provide(PaymentContext $context): array;
}
