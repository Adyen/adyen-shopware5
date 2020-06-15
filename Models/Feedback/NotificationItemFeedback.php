<?php

namespace AdyenPayment\Models\Feedback;

/**
 * Class NotificationItemFeedback
 * @package AdyenPayment\Models\Feedback
 */
class NotificationItemFeedback
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $notificationItem;

    /**
     * NotificationFeedback constructor.
     * @param string $message
     * @param array $notificationItem
     */
    public function __construct(
        string $message,
        array $notificationItem
    ) {
        $this->message = $message;
        $this->notificationItem = $notificationItem;
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
     * @return NotificationItemFeedback
     */
    public function setMessage(string $message): NotificationItemFeedback
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return array
     */
    public function getNotificationItem(): array
    {
        return $this->notificationItem;
    }

    /**
     * @param array $notificationItem
     * @return NotificationItemFeedback
     */
    public function setNotificationItem(array $notificationItem): NotificationItemFeedback
    {
        $this->notificationItem = $notificationItem;
        return $this;
    }
}
