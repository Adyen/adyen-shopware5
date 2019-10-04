<?php

namespace MeteorAdyen\Components\NotificationProcessor;

use MeteorAdyen\Models\Notification;
use Psr\Log\LoggerInterface;

class Authorisation implements NotificationProcessorInterface
{
    const EVENT_CODE = 'authorisation';

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Authorisation constructor.
     * @param LoggerInterface $logger
     */
    public function __construct(
        LoggerInterface $logger
    ) {
        $this->logger = $logger;
    }

    /**
     * Returns boolean on whether this processor can process the Notification object
     *
     * @param Notification $notification
     * @return boolean
     */
    public function supports(Notification $notification): bool
    {
        return strtolower($notification->getEventCode()) == self::EVENT_CODE;
    }

    /**
     * Actual processing of the notification
     *
     * @param Notification $notification
     * @return void
     */
    public function process(Notification $notification)
    {
        $this->logger->error('Processing', [
            'eventCode' => $notification->getEventCode(),
            'status ' => $notification->getStatus()
        ]);
    }
}
