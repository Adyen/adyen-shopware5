<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Feedback;

/**
 * Class TextNotificationItemFeedback.
 */
class TextNotificationItemFeedback
{
    /**
     * @var string
     */
    private $message;

    /**
     * @var array
     */
    private $textNotificationItem;

    /**
     * TextNotificationItemFeedback constructor.
     */
    public function __construct(
        string $message,
        array $textNotificationItem
    ) {
        $this->message = $message;
        $this->textNotificationItem = $textNotificationItem;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function setMessage(string $message): TextNotificationItemFeedback
    {
        $this->message = $message;

        return $this;
    }

    public function getTextNotificationItem(): array
    {
        return $this->textNotificationItem;
    }

    public function setTextNotificationItem(array $textNotificationItem): TextNotificationItemFeedback
    {
        $this->textNotificationItem = $textNotificationItem;

        return $this;
    }
}
