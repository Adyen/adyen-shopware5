<?php

declare(strict_types=1);

namespace AdyenPayment\Components\NotificationProcessor;

use AdyenPayment\Models\Notification;
use AdyenPayment\Models\NotificationException;

/**
 * Interface NotificationProcessorInterface.
 */
interface NotificationProcessorInterface
{
    public const TAG = 'adyen.payment.notificationprocessor';

    /**
     * Returns boolean on whether this processor can process the Notification object.
     */
    public function supports(Notification $notification): bool;

    /**
     * Actual processing of the notification.
     *
     * @throws NotificationException
     */
    public function process(Notification $notification): void;
}
