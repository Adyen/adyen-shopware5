<?php

declare(strict_types=1);

namespace AdyenPayment\Utils;

final class RequestDataFormatter
{
    const SHOPWARE_KEYS = ['module' => null, 'controller' => null, 'action' => null];
    const ALLOWED_PAYMENT_DETAIL_KEYS = [ // Adyen Checkout API v66
            'redirectResult' => null,
            'MD' => null,
            'PaRes' => null,
            'threeds2.challengeResult' => null,
            'threeds2.fingerprint' => null,
        ];

    public static function forRedirect(array $data): array
    {
        if (!$data) {
            return [];
        }

        return array_diff_key($data, self::SHOPWARE_KEYS);
    }

    public static function forPaymentDetails(array $data): array
    {
        if (!$data) {
            return [];
        }

        return array_intersect_key($data, self::ALLOWED_PAYMENT_DETAIL_KEYS);
    }
}
