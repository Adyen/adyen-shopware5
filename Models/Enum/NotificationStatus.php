<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Enum;

use ReflectionClass;

/**
 * Class NotificationStatus.
 */
class NotificationStatus
{
    public const STATUS_RECEIVED = 'received';
    public const STATUS_HANDLED = 'handled';
    public const STATUS_ERROR = 'error';
    public const STATUS_RETRY = 'retry';
    public const STATUS_FATAL = 'fatal';

    public static function getStatusses(): array
    {
        $reflection = new ReflectionClass(NotificationStatus::class);

        return array_values($reflection->getConstants());
    }
}
