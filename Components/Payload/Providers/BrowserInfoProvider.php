<?php

namespace AdyenPayment\Components\Payload\Providers;

use AdyenPayment\Components\Payload\PaymentContext;
use AdyenPayment\Components\Payload\PaymentPayloadProvider;

/**
 * Class BrowserInfoProvider
 * @package AdyenPayment\Components\Payload\Providers
 */
class BrowserInfoProvider implements PaymentPayloadProvider
{
    /**
     * @param PaymentContext $context
     * @return array
     */
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
