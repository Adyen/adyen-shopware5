<?php

namespace MeteorAdyen\Components\NotificationProcessor;

use MeteorAdyen\Models\Event;
use MeteorAdyen\Models\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;

/**
 * Class CaptureFailed
 * @package MeteorAdyen\Components\NotificationProcessor
 */
class CaptureFailed implements NotificationProcessorInterface
{
    const EVENT_CODE = 'CAPTURE_FAILED';

    /**
     * @var LoggerInterface
     */
    private $logger;
    /**
     * @var ContainerAwareEventManager
     */
    private $eventManager;

    /**
     * Authorisation constructor.
     * @param LoggerInterface $logger
     * @param ContainerAwareEventManager $eventManager
     */
    public function __construct(
        LoggerInterface $logger,
        ContainerAwareEventManager $eventManager
    ) {
        $this->logger = $logger;
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
            Event::NOTIFICATION_PROCESS_CAPTURE_FAILED,
            [
                'order' => $order,
                'notification' => $notification
            ]
        );
    }
}
