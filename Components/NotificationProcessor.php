<?php

declare(strict_types=1);

namespace MeteorAdyen\Components;

use IteratorAggregate;
use MeteorAdyen\Components\NotificationProcessor\NotificationProcessorInterface;
use MeteorAdyen\Models\Notification;
use Psr\Log\LoggerInterface;

/**
 * Class NotificationProcessor
 * @package MeteorAdyen\Components
 */
class NotificationProcessor
{
    /**
     * @var NotificationProcessorInterface[]
     */
    private $handlers;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * NotificationProcessor constructor.
     * @param IteratorAggregate $handlers
     * @param LoggerInterface $logger
     * @throws \Enlight_Event_Exception
     */
    public function __construct(
        IteratorAggregate $handlers,
        LoggerInterface $logger
    ) {
        $this->handlers = $this->findTaggedHandlers($handlers);
        $this->logger = $logger;
    }

    /**
     * Process the notification
     *
     * @param Notification $notification
     * @return void
     */
    public function process(Notification $notification)
    {
        $handlers = $this->findHandlers($notification);

        if (empty($handlers)) {
            $this->logger->notice('No notification handler found',
                [
                    'eventCode' => $notification->getEventCode(),
                    'pspReference' => $notification->getPspReference(),
                    'status' => $notification->getStatus()
                ]);
            return;
        }

        foreach ($handlers as $handler) {
            $handler->process($notification);
        }
    }

    /**
     * Convert tagged handlers to array
     * The magic happens in the DI
     *
     * @param $handlers
     * @return NotificationProcessorInterface[]
     * @throws \Enlight_Event_Exception
     */
    private function findTaggedHandlers($handlers)
    {
        return iterator_to_array($handlers, false);
    }

    /**
     * Finds all handlers that support this type of Notification
     *
     * @param $notification
     * @return NotificationProcessorInterface[]|array
     */
    private function findHandlers($notification)
    {
        $handlers = [];
        foreach ($this->handlers as $handler) {
            if ($handler->supports($notification)) {
                $handlers[] = $handler;
            }
        }
        return $handlers;
    }
}
