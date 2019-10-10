<?php
declare(strict_types=1);

namespace MeteorAdyen\Models;

/**
 * Interface PaymentPayloadProvider
 * @package MeteorAdyen\Models
 */
interface PaymentPayloadProvider
{
    /**
     * @param PayContext $context
     * @return array
     */
    public function provide(PayContext $context): array;
}