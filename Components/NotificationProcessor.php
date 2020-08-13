<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use AdyenPayment\Components\NotificationProcessor\NotificationProcessorInterface;
use AdyenPayment\Exceptions\NoNotificationProcessorFoundException;
use AdyenPayment\Exceptions\OrderNotFoundException;
use AdyenPayment\Models\Enum\NotificationStatus;
use AdyenPayment\Models\Event;
use AdyenPayment\Models\Feedback\NotificationProcessorFeedback;
use AdyenPayment\Models\Notification;
use AdyenPayment\Models\NotificationException;
use Psr\Log\LoggerInterface;
use Shopware\Components\ContainerAwareEventManager;
use Shopware\Components\Model\ModelManager;
use Traversable;

/**
 * Class NotificationProcessor
 * @package AdyenPayment\Components
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
     * @param Traversable $notifications
     * @return \Generator<NotificationProcessorFeedback>
     * @throws \Doctrine\ORM\ORMException
     * @throws \Enlight_Event_Exception
     */
    public function processMany(Traversable $notifications)
    {
        foreach ($notifications as $notification) {
            try {
                yield from $this->process($notification);
            } catch (NoNotificationProcessorFoundException $exception) {
                $this->logger->notice(
                    'No notification processor found',
                    [
                        'eventCode' => $notification->getEventCode(),
                        'pspReference' => $notification->getPspReference(),
                        'status' => $notification->getStatus()
                    ]
                );

                yield new NotificationProcessorFeedback(false, $exception->getMessage(), $notification);
            } catch (OrderNotFoundException $exception) {
                $this->logger->error('No order found for notification', [
                    'eventCode' => $notification->getEventCode(),
                    'status ' => $notification->getStatus()
                ]);
                $this->eventManager->notify(Event::NOTIFICATION_NO_ORDER_FOUND, [
                    'notification' => $notification
                ]);

                yield new NotificationProcessorFeedback(false, $exception->getMessage(), $notification);
            } finally {
                $this->modelManager->flush($notification);
            }
        }
    }

    /**
     * @param Notification $notification
     * @throws NoNotificationProcessorFoundException
     * @throws OrderNotFoundException
     * @throws \Doctrine\ORM\ORMException
     * @throws \Enlight_Event_Exception
     */
    private function process(Notification $notification)
    {
        $processors = $this->findProcessors($notification);

        if (empty($processors)) {
            $notification->setStatus(NotificationStatus::STATUS_FATAL);
            $this->modelManager->persist($notification);
            throw new NoNotificationProcessorFoundException((string)$notification->getId());
        }

        if (!$notification->getOrder()) {
            $notification->setStatus(NotificationStatus::STATUS_FATAL);
            $this->modelManager->persist($notification);
            throw new OrderNotFoundException((string)$notification->getOrderId());
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
                yield new NotificationProcessorFeedback(
                    false,
                    "NotificationException: " . $exception->getMessage(),
                    $notification
                );
            } catch (\Exception $exception) {
                $status = NotificationStatus::STATUS_FATAL;
                $this->logger->notice('General Exception', [
                    'exception' => [
                        'message' => $exception->getMessage(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine()
                    ],
                    'notificationId' => $notification->getId()
                ]);
                yield new NotificationProcessorFeedback(
                    false,
                    "General Exception: " . $exception->getMessage(),
                    $notification
                );
            }
        }

        $notification->setStatus($status);
        $this->modelManager->persist($notification);

        yield new NotificationProcessorFeedback(true, "Processed " . $notification->getId(), $notification);
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
