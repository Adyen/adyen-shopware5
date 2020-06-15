<?php

namespace AdyenPayment\Models\Enum;

use ReflectionClass;

/**
 * Class NotificationStatus
 * @package AdyenPayment\Models\Enum
 */
class NotificationStatus
{
    const STATUS_RECEIVED = 'received';
    const STATUS_HANDLED = 'handled';
    const STATUS_ERROR = 'error';
    const STATUS_RETRY = 'retry';
    const STATUS_FATAL = 'fatal';

    public static function getStatusses(): array
    {
        $reflection = new ReflectionClass(NotificationStatus::class);
        return array_values($reflection->getConstants());
    }
}
