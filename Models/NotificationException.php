<?php

declare(strict_types=1);

namespace AdyenPayment\Models;

use Throwable;

class NotificationException extends \Exception
{
    /** @var Notification */
    private $notification;

    /**
     * NotificationException constructor.
     */
    public function __construct(
        Notification $notification,
        string $message = '',
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->notification = $notification;

        parent::__construct($message, $code, $previous);
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }
}
