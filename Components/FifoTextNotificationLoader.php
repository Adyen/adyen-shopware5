<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;

/**
 * Class FifoNotificationLoader
 * @package AdyenPayment\Components
 */
class FifoTextNotificationLoader
{
    /**
     * @var TextNotificationManager
     */
    private $textNotificationManager;

    /**
     * FifoTextNotificationLoader constructor.
     * @param TextNotificationManager $textNotificationManager
     */
    public function __construct(
        TextNotificationManager $textNotificationManager
    ) {
        $this->textNotificationManager = $textNotificationManager;
    }

    public function get(): array
    {
        try {
            return $this->textNotificationManager->getTextNextNotificationsToHandle();
        } catch (NoResultException | NonUniqueResultException $e) {
            return [];
        }
    }
}
