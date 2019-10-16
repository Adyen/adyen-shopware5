<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\NotificationProcessor;

use MeteorAdyen\Models\Notification;

/**
 * Interface NotificationProcessorInterface
 * @package MeteorAdyen\Components
 */
interface NotificationProcessorInterface
{
    /**
     * Returns boolean on whether this processor can process the Notification object
     *
     * @param Notification $notification
     * @return boolean
     */
    public function supports(Notification $notification): bool;

    /**
     * Actual processing of the notification
     *
     * @param Notification $notification
     * @return void
     */
    public function process(Notification $notification);
}