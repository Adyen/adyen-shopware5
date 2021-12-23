<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload;

/**
 * Class Chain.
 */
class Chain implements PaymentPayloadProvider
{
    /**
     * @var PaymentPayloadProvider[]
     */
    private $providers;

    /**
     * Chain constructor.
     *
     * @param PaymentPayloadProvider ...$providers
     */
    public function __construct(
        PaymentPayloadProvider ...$providers
    ) {
        $this->providers = $providers;
    }

    public function provide(PaymentContext $context): array
    {
        return array_reduce(
            $this->providers,
            static function(array $payload, PaymentPayloadProvider $provider) use ($context): array {
                return array_merge_recursive($payload, $provider->provide($context));
            },
            []
        );
    }
}
