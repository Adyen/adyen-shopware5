<?php
declare(strict_types=1);

namespace MeteorAdyen\Components\Payload\Providers;

use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\PaymentPayloadProvider;

/**
 * Class ApplicationInfoProvider
 * @package MeteorAdyen\Components
 */
class ShopperInfoProvider implements PaymentPayloadProvider
{
    /**
     * ApplicationInfoProvider constructor.
     */
    public function __construct()
    {
    }

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