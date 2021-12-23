<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

/**
 * Class FifoNotificationLoader.
 */
class FifoTextNotificationLoader
{
    /**
     * @var TextNotificationManager
     */
    private $textNotificationManager;

    /**
     * FifoTextNotificationLoader constructor.
     */
    public function __construct(
        TextNotificationManager $textNotificationManager
    ) {
        $this->textNotificationManager = $textNotificationManager;
    }

    public function get(): array
    {
        return $this->textNotificationManager->getTextNextNotificationsToHandle();
    }
}
