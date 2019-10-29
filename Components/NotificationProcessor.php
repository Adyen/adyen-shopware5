<?php

declare(strict_types=1);

namespace MeteorAdyen\Components;

use IteratorAggregate;
use MeteorAdyen\Components\NotificationProcessor\NotificationProcessorInterface;
use MeteorAdyen\Models\Enum\NotificationStatus;
use MeteorAdyen\Models\Event;
use MeteorAdyen\Models\Notification;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;

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
     * @var ModelManager
     */
    private $modelManager;
    /**
     * @var ContainerAwareEventManager
     */
    private $eventManager;

    /**
     * NotificationProcessor constructor.
     * @param IteratorAggregate $handlers
     * @param LoggerInterface $logger
     * @param ModelManager $modelManager
     * @param ContainerAwareEventManager $eventManager
     * @throws \Enlight_Event_Exception
     */
    public function __construct(
        IteratorAggregate $handlers,
        LoggerInterface $logger,
        ModelManager $modelManager,
        ContainerAwareEventManager $eventManager
    ) {
        $this->handlers = $this->findTaggedHandlers($handlers);
        $this->logger = $logger;
        $this->modelManager = $modelManager;
        $this->eventManager = $eventManager;
    }

    /**
     * Process the notification
     *
     * @param Notification $notification
     * @throws \Doctrine\ORM\ORMException
     * @throws \Enlight_Event_Exception
     */
    public function process(Notification $notification)
    {
        $handlers = $this->findHandlers($notification);

        if (empty($handlers)) {
            $notification->setStatus(NotificationStatus::STATUS_FATAL);
            $this->modelManager->persist($notification);

            $this->logger->notice('No notification handler found',
                [
                    'eventCode' => $notification->getEventCode(),
                    'pspReference' => $notification->getPspReference(),
                    'status' => $notification->getStatus()
                ]);
            return;
        }

        if (!$notification->getOrder()) {
            $notification->setStatus(NotificationStatus::STATUS_FATAL);
            $this->modelManager->persist($notification);

            $this->logger->error('No order found for notification', [
                'eventCode' => $notification->getEventCode(),
                'status ' => $notification->getStatus()
            ]);
            $this->eventManager->notify(Event::NOTIFICATION_NO_ORDER_FOUND, [
                'notification' => $notification
            ]);
            return;
        }

        foreach ($handlers as $handler) {
            $handler->process($notification);
        }

        $notification->setStatus(NotificationStatus::STATUS_HANDLED);
        $this->modelManager->persist($notification);
    }

    /**
     * Convert tagged handlers to array and check if they are actually Notification Processors
     * The real magic happens in the DI
     *
     * @param $handlers
     * @return NotificationProcessorInterface[]
     * @throws \Enlight_Event_Exception
     */
    private function findTaggedHandlers($handlers)
    {
        $handlers = iterator_to_array($handlers, false);
        $handlers = array_filter($handlers, function($handler) {
            return $handler instanceof NotificationProcessorInterface;
        });
        return $handlers;
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
