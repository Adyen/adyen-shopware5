<?php
declare(strict_types=1);

namespace MeteorAdyen\Models\Payload\Providers;

/**
 * Class LineItemsInfoProvider
 * @package MeteorAdyen\Models\Payload\Providers
 */
class LineItemsInfoProvider implements PaymentPayloadProvider
{
    /**
     * LineItemsInfoProvider constructor.
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