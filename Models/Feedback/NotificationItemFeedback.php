<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Feedback;

/**
 * Class NotificationItemFeedback.
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
     */
    public function __construct(
        string $message,
        array $notificationItem
    ) {
        $this->message = $message;
        $this->notificationItem = $notificationItem;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): NotificationItemFeedback
    {
        $this->message = $message;

        return $this;
    }

    public function getNotificationItem(): array
    {
        return $this->notificationItem;
    }

    public function setNotificationItem(array $notificationItem): NotificationItemFeedback
    {
        $this->notificationItem = $notificationItem;

        return $this;
    }
}
