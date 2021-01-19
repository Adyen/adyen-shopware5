<?php

declare(strict_types=1);

namespace AdyenPayment\Utils;

final class RequestDataFormatter
{
    const SHOPWARE_KEYS = ['module' => null, 'controller' => null, 'action' => null];

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

        return array_diff_key($data, array_merge(self::SHOPWARE_KEYS, ['merchantReference' => null]));
    }
}
