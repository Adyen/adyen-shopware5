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

    public function __construct(
        IteratorAggregate $handlers,
        LoggerInterface $logger
    ) {
        $this->handlers = iterator_to_array($handlers, false);
        $this->logger = $logger;
    }

    /**
     * @param Notification $notification
     * @return void
     */
    public function process(Notification $notification)
    {
        $handler = $this->findHandler($notification);

        if (!$handler) {
            $this->logger->notice('No notification handler found',
                [
                    'eventCode' => $notification->getEventCode(),
                    'pspReference' => $notification->getPspReference(),
                    'status' => $notification->getStatus()
                ]);
            return;
        }

        $handler->process($notification);
    }

    /**
     * @param $notification
     * @return NotificationProcessorInterface|null
     */
    private function findHandler($notification)
    {
        foreach ($this->handlers as $handler) {
            if ($handler->supports($notification)) {
                return $handler;
            }
        }
        return null;
    }
}
