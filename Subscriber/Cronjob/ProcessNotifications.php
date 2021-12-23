<?php

declare(strict_types=1);

namespace AdyenPayment\Subscriber\Cronjob;

use AdyenPayment\Components\FifoNotificationLoader;
use AdyenPayment\Components\FifoTextNotificationLoader;
use AdyenPayment\Components\IncomingNotificationManager;
use AdyenPayment\Components\NotificationProcessor;
use AdyenPayment\Models\Event;
use AdyenPayment\Models\Feedback\NotificationProcessorFeedback;
use Enlight\Event\SubscriberInterface;
use Psr\Log\LoggerInterface;
use Shopware_Components_Cron_CronJob;

/**
 * Class ProcessNotifications.
 */
class ProcessNotifications implements SubscriberInterface
{
    public const NUMBER_OF_NOTIFICATIONS_TO_HANDLE = 20;

    /**
     * @var FifoNotificationLoader
     */
    private $fifoNotificationLoader;

    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @var FifoTextNotificationLoader
     */
    private $fifoTextNotificationLoader;

    /**
     * @var IncomingNotificationManager
     */
    private $incomingNotificationManager;

    /**
     * ProcessNotifications constructor.
     */
    public function __construct(
        FifoNotificationLoader $fifoNotificationLoader,
        FifoTextNotificationLoader $fifoTextNotificationLoader,
        NotificationProcessor $notificationProcessor,
        IncomingNotificationManager $incomingNotificationManager,
        LoggerInterface $logger
    ) {
        $this->fifoNotificationLoader = $fifoNotificationLoader;
        $this->fifoTextNotificationLoader = $fifoTextNotificationLoader;
        $this->notificationProcessor = $notificationProcessor;
        $this->incomingNotificationManager = $incomingNotificationManager;
        $this->logger = $logger;
    }

    /**
     * @return string[]
     *
     * @psalm-return array<string, 'runCronjob'>
     */
    public static function getSubscribedEvents()
    {
        return [
            Event::cronProcessNotifications()->getName() => 'runCronjob',
        ];
    }

    /**
     * @throws \Doctrine\ORM\ORMException
     * @throws \Enlight_Event_Exception
     */
    public function runCronjob(Shopware_Components_Cron_CronJob $job): void
    {
        $textNotifications = $this->fifoTextNotificationLoader->get();
        $this->incomingNotificationManager->convertNotifications($textNotifications);

        /** @var \Generator<NotificationProcessorFeedback> $feedback */
        $feedback = $this->notificationProcessor->processMany(
            $this->fifoNotificationLoader->load(self::NUMBER_OF_NOTIFICATIONS_TO_HANDLE)
        );

        /** @var NotificationProcessorFeedback $item */
        foreach ($feedback as $item) {
            if (!$item->isSuccess()) {
                $this->logger->alert($item->getNotification()->getId().': '.$item->getMessage());
            }
        }
    }
}
