<?php

declare(strict_types=1);

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Models\Event;
use AdyenPayment\Models\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;

/**
 * Class CaptureFailed.
 */
class CaptureFailed implements NotificationProcessorInterface
{
    public const EVENT_CODE = 'CAPTURE_FAILED';

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
     */
    public function __construct(
        LoggerInterface $logger,
        ContainerAwareEventManager $eventManager
    ) {
        $this->logger = $logger;
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
            Event::NOTIFICATION_PROCESS_CAPTURE_FAILED,
            [
                'order' => $order,
                'notification' => $notification,
            ]
        );
    }
}
