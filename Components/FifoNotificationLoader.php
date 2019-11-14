<?php

namespace MeteorAdyen\Components;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use MeteorAdyen\Models\Enum\NotificationStatus;
use MeteorAdyen\Models\Notification;
use Shopware\Components\Model\ModelManager;

/**
 * Class FifoNotificationLoader
 * @package MeteorAdyen\Components
 */
class FifoNotificationLoader
{
    /**
     * @var NotificationManager
     */
    private $notificationManager;

    /**
     * FifoNotificationLoader constructor.
     * @param NotificationManager $notificationManager
     */
    public function __construct(
        NotificationManager $notificationManager
    ) {
        $this->notificationManager = $notificationManager;
    }

    /**
     * @param int $amount
     * @return \Generator
     */
    public function load(int $amount): \Generator
    {
        try {
            yield $this->notificationManager->getNextNotificationToHandle();
            if ($amount > 1) {
                yield from $this->load($amount-1);
            }
        } catch (NoResultException $exception) {
            return;
        } catch (NonUniqueResultException $exception) {
            return;
        }
    }
}
