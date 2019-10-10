<?php

declare(strict_types=1);

namespace MeteorAdyen\Subscriber\Notification;

use Doctrine\ORM\EntityManagerInterface;
use Enlight\Event\SubscriberInterface;
use Enlight_Event_EventArgs;
use MeteorAdyen\Components\Builder\NotificationBuilder;
use MeteorAdyen\Models\Event;

/**
 * Class SaveNotification
 */
class SaveNotification implements SubscriberInterface
{
    /**
     * @var NotificationBuilder
     */
    private $notificationBuilder;
    /**
     * @var EntityManagerInterface
     */
    private $entityManager;

    /**
     * SaveNotification constructor.
     * @param NotificationBuilder $notificationBuilder
     * @param EntityManagerInterface $entityManager
     */
    public function __construct(
        NotificationBuilder $notificationBuilder,
        EntityManagerInterface $entityManager
    ) {
        $this->notificationBuilder = $notificationBuilder;
        $this->entityManager = $entityManager;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            Event::NOTIFICATION_ON_SAVE_NOTIFICATIONS => 'saveNotifications'
        ];
    }

    /**
     * @param Enlight_Event_EventArgs $args
     */
    public function saveNotifications(Enlight_Event_EventArgs $args)
    {
        $params = $args->get('params');

        foreach ($params as $notificationItem) {
            if ($notification = $this->notificationBuilder->fromParams($notificationItem['NotificationRequestItem'])) {
                $this->entityManager->persist($notification);
            }
        }
        $this->entityManager->flush();
    }
}
