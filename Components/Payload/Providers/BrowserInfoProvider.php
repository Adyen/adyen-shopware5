<?php
declare(strict_types=1);

namespace MeteorAdyen\Models\Payload\Providers;

/**
 * Class BrowserInfoProvider
 * @package MeteorAdyen\Models\Payload\Providers
 */
class BrowserInfoProvider implements PaymentPayloadProvider
{
    /**
     * BrowserInfoProvider constructor.
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