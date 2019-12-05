<?php

namespace MeteorAdyen\Models\Feedback;

use MeteorAdyen\Models\Notification;

/**
 * Class NotificationProcessorFeedback
 * @package MeteorAdyen\Models\Feedback
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
     * NotificationProcessorFeedback constructor.
     * @param string $message
     * @param Notification $notification
     */
    public function __construct(
        string $message,
        Notification $notification
    ) {
        $this->message = $message;
        $this->notification = $notification;
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
