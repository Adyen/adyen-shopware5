<?php

namespace AdyenPayment\Models\Feedback;

use AdyenPayment\Models\Notification;

/**
 * Class NotificationProcessorFeedback
 * @package AdyenPayment\Models\Feedback
 */
class NotificationProcessorFeedback
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var Notification
     */
    private $notification;

    /**
     * @var bool
     */
    private $success;

    /**
     * NotificationProcessorFeedback constructor.
     * @param bool $success
     * @param string $message
     * @param Notification $notification
     */
    public function __construct(
        bool $success,
        string $message,
        Notification $notification
    ) {
        $this->success = $success;
        $this->message = $message;
        $this->notification = $notification;
    }

    /**
     * @return bool
     */
    public function isSuccess(): bool
    {
        return $this->success;
    }

    /**
     * @param bool $success
     * @return NotificationProcessorFeedback
     */
    public function setSuccess(bool $success): NotificationProcessorFeedback
    {
        $this->success = $success;
        return $this;
    }

    /**
     * @return string
     */
    public function getMessage(): string
    {
        return $this->message;
    }

    /**
     * @param string $message
     * @return NotificationProcessorFeedback
     */
    public function setMessage(string $message): NotificationItemFeedback
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return Notification
     */
    public function getNotification(): Notification
    {
        return $this->notification;
    }

    /**
     * @param Notification $notification
     * @return NotificationProcessorFeedback
     */
    public function setNotification(Notification $notification): NotificationProcessorFeedback
    {
        $this->notification = $notification;
        return $this;
    }
}
