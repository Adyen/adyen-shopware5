<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

/**
 * Class NoNotificationProcessorFoundException.
 */
class NoNotificationProcessorFoundException extends \Exception
{
    /**
     * NoNotificationProcessorFoundException constructor.
     */
    public function __construct(string $notificationId)
    {
        parent::__construct('No Notification Processor could be found to process notification with ID '.
            $notificationId);
    }
}
