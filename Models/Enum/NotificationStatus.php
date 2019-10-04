<?php

namespace MeteorAdyen\Models\Enum;

/**
 * Class NotificationStatus
 */
class NotificationStatus
{
    const STATUS_RECEIVED = 'received';
    const STATUS_HANDLED = 'handled';
    const STATUS_ERROR = 'error';
    const STATUS_RETRY = 'retry';
    const STATUS_FATAL = 'fatal';
}
