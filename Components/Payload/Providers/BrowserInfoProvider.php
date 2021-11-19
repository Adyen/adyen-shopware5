<?php

declare(strict_types=1);

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

/**
 * Class BrowserInfoProvider.
 */
class BrowserInfoProvider implements PaymentPayloadProvider
{
    public function provide(PaymentContext $context): array
    {
        $browserInfo = [];

        if (!empty($_SERVER['HTTP_ACCEPT'])) {
            $browserInfo['acceptHeader'] = $_SERVER['HTTP_ACCEPT'];
        }

        return [
            'browserInfo' => array_merge($browserInfo, $context->getBrowserInfo()),
        ];
    }
}
