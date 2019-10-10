<?php
declare(strict_types=1);

namespace MeteorAdyen\Models;

/**
 * Class Chain
 * @package MeteorAdyen\Models
 */
class Chain implements PaymentPayloadProvider
{
    /**
     * @var PaymentPayloadProvider[]
     */
    private $providers;

    /**
     * Chain constructor.
     * @param PaymentPayloadProvider ...$providers
     */
    public function __construct(
        PaymentPayloadProvider ... $providers
    ) {
        $this->providers = $providers;
    }

    /**
     * @param PayContext $context
     * @return array
     */
    public function provide(PayContext $context): array
    {
        return array_reduce(
            $this->providers,
            function (array $payload, PaymentPayloadProvider $provider) use ($context) : array {
                return array_merge_recursive($payload, $provider->provide($context));
            },
            []
        );
    }
}