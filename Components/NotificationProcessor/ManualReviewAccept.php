<?php

declare(strict_types=1);

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Models\Event;
use AdyenPayment\Models\Notification;
use Shopware\Components\ContainerAwareEventManager;

/**
 * Class ManualReviewReject.
 */
class ManualReviewAccept implements NotificationProcessorInterface
{
    public const EVENT_CODE = 'MANUAL_REVIEW_ACCEPT';

    /**
     * @var ContainerAwareEventManager
     */
    private $eventManager;

    /**
     * Cancellation constructor.
     */
    public function __construct(
        ContainerAwareEventManager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * Returns boolean on whether this processor can process the Notification object.
     */
    public function supports(Notification $notification): bool
    {
        return self::EVENT_CODE === mb_strtoupper($notification->getEventCode());
    }

    /**
     * Actual processing of the notification.
     *
     * @throws \Enlight_Event_Exception
     */
    public function process(Notification $notification): void
    {
        $order = $notification->getOrder();

        $this->eventManager->notify(
            Event::NOTIFICATION_PROCESS_CANCELLATION,
            [
                'order' => $order,
                'notification' => $notification,
            ]
        );
    }
}
