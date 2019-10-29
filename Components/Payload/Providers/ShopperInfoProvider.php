<?php
declare(strict_types=1);

namespace MeteorAdyen\Components\Payload\Providers;

use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\PaymentPayloadProvider;

/**
 * Class ShopperInfoProvider
 * @package MeteorAdyen\Components\Payload\Providers
 */
class ShopperInfoProvider implements PaymentPayloadProvider
{
    /**
     * @param PaymentContext $context
     * @return array
     */
    public function provide(PaymentContext $context): array
    {
        // TODO: Implement provide() method.
        return [];
    }
}