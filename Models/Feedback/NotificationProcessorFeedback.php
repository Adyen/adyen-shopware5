<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Feedback;

use AdyenPayment\Models\Notification;

class NotificationProcessorFeedback
{
    /** @var string */
    private $message;

    /** @var Notification */
    private $notification;

    /** @var bool */
    private $success;

    public function __construct(
        bool $success,
        string $message,
        Notification $notification
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->notification = $notification;
    }

    public function isSuccess(): bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): NotificationProcessorFeedback
    {
        $this->success = $success;

        return $this;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): NotificationProcessorFeedback
    {
        $this->message = $message;

        return $this;
    }

    public function getNotification(): Notification
    {
        return $this->notification;
    }

    public function setNotification(Notification $notification): NotificationProcessorFeedback
    {
        $this->notification = $notification;

        return $this;
    }
}
