<?php
declare(strict_types=1);

namespace MeteorAdyen\Components\Payload\Providers;

use MeteorAdyen\Components\Payload\PaymentContext;
use MeteorAdyen\Components\Payload\PaymentPayloadProvider;

/**
 * Class BrowserInfoProvider
 * @package MeteorAdyen\Components\Payload\Providers
 */
class BrowserInfoProvider implements PaymentPayloadProvider
{
    /**
     * @param PaymentContext $context
     * @return array
     */
    public function provide(PaymentContext $context): array
    {
        $browserInfo = [
            'acceptHeader' => $_SERVER['HTTP_ACCEPT'] ?? '',
        ];

        return [
            'browserInfo' => array_merge($browserInfo, $context->getBrowserInfo()),
        ];
    }
}