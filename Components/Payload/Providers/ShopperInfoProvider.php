<?php
declare(strict_types=1);

namespace MeteorAdyen\Models\Payload\Providers;

/**
 * Class ApplicationInfoProvider
 * @package MeteorAdyen\Models
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
     * @param PayContext $context
     * @return array
     */
    public function provide(PayContext $context): array
    {
        // TODO: Implement provide() method.
        return [];
    }
}