<?php

declare(strict_types=1);

namespace MeteorAdyen\Components\NotificationProcessor;

use MeteorAdyen\Models\Notification;
use MeteorAdyen\Models\NotificationException;

/**
 * Interface NotificationProcessorInterface
 * @package MeteorAdyen\Components
 */
interface NotificationProcessorInterface
{
    const TAG = 'meteor.adyen.notificationprocessor';

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
     * @throws NotificationException
     */
    public function process(Notification $notification);
}
