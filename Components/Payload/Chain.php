<?php

namespace MeteorAdyen\Components\Payload;

/**
 * Class Chain
 * @package MeteorAdyen\Components
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
        PaymentPayloadProvider ...$providers
    ) {
        $this->providers = $providers;
    }

    /**
     * @param PayContext $context
     * @return array
     */
    public function provide(PaymentContext $context): array
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
