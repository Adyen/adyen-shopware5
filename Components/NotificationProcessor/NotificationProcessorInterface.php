<?php

declare(strict_types=1);

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Models\Notification;
use AdyenPayment\Models\NotificationException;

/**
 * Interface NotificationProcessorInterface
 * @package AdyenPayment\Components
 */
interface NotificationProcessorInterface
{
    const TAG = 'adyen.payment.notificationprocessor';

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
