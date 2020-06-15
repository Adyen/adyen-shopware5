<?php

namespace AdyenPayment\Models;

use Throwable;

class NotificationException extends \Exception
{
    /**
     * @var Notification
     */
    private $notification;

    /**
     * NotificationException constructor.
     * @param Notification $notification
     * @param string $message
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        Notification $notification,
        string $message = "",
        int $code = 0,
        Throwable $previous = null
    ) {
        $this->notification = $notification;

        parent::__construct($message, $code, $previous);
    }

    /**
     * @return Notification
     */
    public function getNotification(): Notification
    {
        return $this->notification;
    }
}
