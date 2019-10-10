<?php

namespace MeteorAdyen\Subscriber\Cronjob;

use Doctrine\ORM\NoResultException;
use MeteorAdyen\Components\NotificationManager;
use MeteorAdyen\Components\NotificationProcessor;

/**
 * Class ProcessNotifications
 * @package MeteorAdyen\Subscriber\Cronjob
 */
class ProcessNotifications implements \Enlight\Event\SubscriberInterface
{
    const NUMBER_OF_NOTIFICATIONS_TO_HANDLE = 20;

    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;
    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * ProcessNotifications constructor.
     * @param NotificationProcessor $notificationProcessor
     * @param NotificationManager $notificationManager
     */
    public function __construct(
        NotificationProcessor $notificationProcessor,
        NotificationManager $notificationManager
    ) {
        $this->notificationProcessor = $notificationProcessor;
        $this->notificationManager = $notificationManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Meteor_Adyen_CronJob_ProcessNotifications' => 'runCronjob'
        ];
    }

    /**
     * @param \Shopware_Components_Cron_CronJob $job
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function runCronjob(\Shopware_Components_Cron_CronJob $job)
    {
        for ($i = 0; $i = self::NUMBER_OF_NOTIFICATIONS_TO_HANDLE; $i++) {
            try {
                $notification = $this->notificationManager->getNextNotificationToHandle();
            } catch (NoResultException $exception) {
                return;
            }
            $this->notificationProcessor->process($notification);
        }
    }
}
