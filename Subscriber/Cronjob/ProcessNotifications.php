<?php

namespace MeteorAdyen\Subscriber\Cronjob;

use MeteorAdyen\Components\FifoNotificationLoader;
use MeteorAdyen\Components\NotificationProcessor;

/**
 * Class ProcessNotifications
 * @package MeteorAdyen\Subscriber\Cronjob
 */
class ProcessNotifications implements \Enlight\Event\SubscriberInterface
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
     * ProcessNotifications constructor.
     * @param FifoNotificationLoader $fifoNotificationLoader
     * @param NotificationProcessor $notificationProcessor
     */
    public function __construct(
        FifoNotificationLoader $fifoNotificationLoader,
        NotificationProcessor $notificationProcessor
    ) {
        $this->loader = $fifoNotificationLoader;
        $this->notificationProcessor = $notificationProcessor;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Meteor_Adyen_CronJob_ProcessNotifications' => 'runCronjob'
        ];
    }

    /**
     * @param \Shopware_Components_Cron_CronJob $job
     * @throws \Doctrine\ORM\ORMException
     * @throws \Enlight_Event_Exception
     */
    public function runCronjob(\Shopware_Components_Cron_CronJob $job)
    {
        $this->notificationProcessor->processMany(
            $this->loader->load(self::NUMBER_OF_NOTIFICATIONS_TO_HANDLE)
        );
    }
}
