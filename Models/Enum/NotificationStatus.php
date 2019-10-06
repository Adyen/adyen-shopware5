<?php

namespace MeteorAdyen\Models\Enum;

/**
 * Class NotificationStatus
 * @package MeteorAdyen\Models\Enum
 */
class NotificationStatus
{
    const STATUS_RECEIVED = 'received';
    const STATUS_HANDLED = 'handled';
    const STATUS_ERROR = 'error';
    const STATUS_RETRY = 'retry';
    const STATUS_FATAL = 'fatal';
}
