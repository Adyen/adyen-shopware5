<?php

namespace AdyenPayment\Models\Feedback;

/**
 * Class TextNotificationItemFeedback
 * @package AdyenPayment\Models\Feedback
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
     * @param string $message
     * @param array $textNotificationItem
     */
    public function __construct(
        string $message,
        array $textNotificationItem
    ) {
        $this->message = $message;
        $this->textNotificationItem = $textNotificationItem;
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
     * @return TextNotificationItemFeedback
     */
    public function setMessage(string $message): TextNotificationItemFeedback
    {
        $this->message = $message;
        return $this;
    }

    /**
     * @return array
     */
    public function getTextNotificationItem(): array
    {
        return $this->textNotificationItem;
    }

    /**
     * @param array $textNotificationItem
     * @return TextNotificationItemFeedback
     */
    public function setTextNotificationItem(array $textNotificationItem): TextNotificationItemFeedback
    {
        $this->textNotificationItem = $textNotificationItem;
        return $this;
    }
}
