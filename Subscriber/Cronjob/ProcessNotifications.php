<?php

namespace MeteorAdyen\Subscriber\Cronjob;

use MeteorAdyen\Components\NotificationProcessor;
use MeteorAdyen\Models\Notification;
use Shopware\Components\Model\ModelManager;

class ProcessNotifications implements \Enlight\Event\SubscriberInterface
{
    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * ProcessNotifications constructor.
     * @param NotificationProcessor $notificationProcessor
     * @param ModelManager $modelManager
     */
    public function __construct(
        NotificationProcessor $notificationProcessor,
        ModelManager $modelManager
    ) {
        $this->notificationProcessor = $notificationProcessor;
        $this->modelManager = $modelManager;
    }

    public static function getSubscribedEvents()
    {
        return [
            'Meteor_Adyen_CronJob_ProcessNotifications' => 'runCronjob'
        ];
    }

    public function runCronjob(\Shopware_Components_Cron_CronJob $job)
    {
        $repository = $this->modelManager->getRepository(Notification::class);
        $qb = $repository->createQueryBuilder('getNotification');
        $qb->select()
            ->orderBy('getNotification.id', 'ASC');
        /** @var Notification $notification */
        $notification = $qb->getQuery()->getResult();

        var_dump($notification);

        if (!$notification) {
            // not found
            return;
        }

        $this->notificationProcessor->process($notification);
    }
}
