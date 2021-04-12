<?php

declare(strict_types=1);

namespace AdyenPayment\Utils;

final class RequestDataFormatter
{
    const ALLOWED_PAYMENT_DETAIL_V67_KEYS = [
        'billingToken' => null,
        'cupsecureplus.smscode' => null,
        'facilitatorAccessToken' => null,
        'oneTimePasscode' => null,
        'orderID' => null,
        'payerID' => null,
        'payload' => null,
        'paymentID' => null,
        'paymentStatus' => null,
        'redirectResult' => null,
        'threeDSResult' => null,
        'threeds2.challengeResult' => null,
        'threeds2.fingerprint' => null,
    ];

    public static function forPaymentDetails(array $data): array
    {
        if (!$data) {
            return [];
        }

        return array_intersect_key($data, self::ALLOWED_PAYMENT_DETAIL_V67_KEYS);
    }
}
