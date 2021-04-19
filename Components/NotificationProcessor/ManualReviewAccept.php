<?php

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Models\Event;
use AdyenPayment\Models\Notification;
use Shopware\Components\ContainerAwareEventManager;

/**
 * Class ManualReviewReject
 * @package AdyenPayment\Components\NotificationProcessor
 */
class ManualReviewAccept implements NotificationProcessorInterface
{
    const EVENT_CODE = 'MANUAL_REVIEW_ACCEPT';

    /**
     * @var ContainerAwareEventManager
     */
    private $eventManager;

    /**
     * Cancellation constructor.
     * @param ContainerAwareEventManager $eventManager
     */
    public function __construct(
        ContainerAwareEventManager $eventManager
    ) {
        $this->eventManager = $eventManager;
    }

    /**
     * Returns boolean on whether this processor can process the Notification object
     *
     * @param Notification $notification
     * @return boolean
     */
    public function supports(Notification $notification): bool
    {
        return strtoupper($notification->getEventCode()) === self::EVENT_CODE;
    }

    /**
     * Actual processing of the notification
     *
     * @param Notification $notification
     * @throws \Enlight_Event_Exception
     */
    public function process(Notification $notification)
    {
        $order = $notification->getOrder();

        $this->eventManager->notify(
            Event::NOTIFICATION_PROCESS_CANCELLATION,
            [
                'order' => $order,
                'notification' => $notification
            ]
        );
    }
}
