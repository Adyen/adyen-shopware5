<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Class FifoNotificationLoader.
 */
class FifoNotificationLoader
{
    /** @var NotificationManager */
    private $notificationManager;

    /**
     * FifoNotificationLoader constructor.
     */
    public function __construct(
        NotificationManager $notificationManager
    ) {
        $this->notificationManager = $notificationManager;
    }

    public function load(int $amount): \Generator
    {
        try {
            yield $this->notificationManager->getNextNotificationToHandle();
            if ($amount > 1) {
                yield from $this->load($amount - 1);
            }
        } catch (NoResultException $exception) {
            return;
        } catch (NonUniqueResultException $exception) {
            return;
        }
    }
}
