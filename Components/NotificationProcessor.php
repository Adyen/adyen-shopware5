<?php

declare(strict_types=1);

namespace MeteorAdyen\Components;

use IteratorAggregate;
use MeteorAdyen\Components\NotificationProcessor\NotificationProcessorInterface;
use MeteorAdyen\Models\Enum\NotificationStatus;
use MeteorAdyen\Models\Event;
use MeteorAdyen\Models\Notification;
use MeteorAdyen\Models\NotificationException;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Traversable;

/**
 * Class NotificationProcessor
 * @package MeteorAdyen\Components
 */
class NotificationProcessor
{
    /**
     * @var NotificationProcessorInterface[]
     */
    private $processors;
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
     * @param LoggerInterface $logger
     * @param ModelManager $modelManager
     * @param ContainerAwareEventManager $eventManager
     * @throws \Enlight_Event_Exception
     */
    public function __construct(
        LoggerInterface $logger,
        ModelManager $modelManager,
        ContainerAwareEventManager $eventManager
    ) {
        $this->logger = $logger;
        $this->modelManager = $modelManager;
        $this->eventManager = $eventManager;
    }

    /**
     * @param Traversable|Notification[] $notifications
     * @throws \Doctrine\ORM\ORMException
     * @throws \Enlight_Event_Exception
     */
    public function processMany(Traversable $notifications)
    {
        foreach ($notifications as $notification) {
            $this->process($notification);
        }
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
        $processors = $this->findProcessors($notification);

        if (empty($processors)) {
            $notification->setStatus(NotificationStatus::STATUS_FATAL);
            $this->modelManager->persist($notification);

            $this->logger->notice(
                'No notification processor found',
                [
                    'eventCode' => $notification->getEventCode(),
                    'pspReference' => $notification->getPspReference(),
                    'status' => $notification->getStatus()
                ]
            );
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

        $status = NotificationStatus::STATUS_HANDLED;
        foreach ($processors as $processor) {
            try {
                $processor->process($notification);
            } catch (NotificationException $exception) {
                $status = NotificationStatus::STATUS_ERROR;
                $this->logger->notice('NotificationException', [
                    'message' => $exception->getMessage(),
                    'notificationId' => $exception->getNotification()->getId()
                ]);
            } catch (\Exception $exception) {
                $status = NotificationStatus::STATUS_FATAL;
                $this->logger->notice('General Exception', [
                    'exception' => $exception,
                    'notificationId' => $notification->getId()
                ]);
            }
        }

        $notification->setStatus($status);
        $this->modelManager->persist($notification);
    }

    /**
     * @param NotificationProcessorInterface $processor
     */
    public function addProcessor(NotificationProcessorInterface $processor)
    {
        $this->processors[] = $processor;
    }

    /**
     * Finds all processors that support this type of Notification
     *
     * @param $notification
     * @return array
     */
    private function findProcessors($notification): array
    {
        $processors = [];
        foreach ($this->processors as $processor) {
            if ($processor->supports($notification)) {
                $processors[] = $processor;
            }
        }
        return $processors;
    }
}
