<?php

namespace AdyenPayment\Subscriber\Cronjob;

use AdyenPayment\Components\FifoNotificationLoader;
use AdyenPayment\Components\NotificationProcessor;
use AdyenPayment\Models\Feedback\NotificationProcessorFeedback;
use Enlight\Event\SubscriberInterface;
use Psr\Log\LoggerInterface;
use Shopware_Components_Cron_CronJob;

/**
 * Class ProcessNotifications
 * @package AdyenPayment\Subscriber\Cronjob
 */
class ProcessNotifications implements SubscriberInterface
{
    const NUMBER_OF_NOTIFICATIONS_TO_HANDLE = 20;

    /**
     * @var FifoNotificationLoader
     */
    private $loader;
    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;
    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * ProcessNotifications constructor.
     * @param FifoNotificationLoader $fifoNotificationLoader
     * @param NotificationProcessor $notificationProcessor
     */
    public function __construct(
        FifoNotificationLoader $fifoNotificationLoader,
        NotificationProcessor $notificationProcessor,
        LoggerInterface $logger
    ) {
        $this->loader = $fifoNotificationLoader;
        $this->notificationProcessor = $notificationProcessor;
        $this->logger = $logger;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Shopware_CronJob_AdyenPaymentProcessNotifications' => 'runCronjob'
        ];
    }

    /**
     * @param Shopware_Components_Cron_CronJob $job
     * @throws \Doctrine\ORM\ORMException
     * @throws \Enlight_Event_Exception
     */
    public function runCronjob(Shopware_Components_Cron_CronJob $job)
    {
        /** @var \Generator<NotificationProcessorFeedback> $feedback */
        $feedback = $this->notificationProcessor->processMany(
            $this->loader->load(self::NUMBER_OF_NOTIFICATIONS_TO_HANDLE)
        );

        /** @var NotificationProcessorFeedback $item */
        foreach ($feedback as $item) {
            if (!$item->isSuccess()) {
                $this->logger->alert($item->getNotification()->getId() . ": " . $item->getMessage());
            }
        }
    }
}
