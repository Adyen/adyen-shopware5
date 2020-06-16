<?php

namespace AdyenPayment\Exceptions;

/**
 * Class NoNotificationProcessorFoundException
 * @package AdyenPayment\Exceptions
 */
class NoNotificationProcessorFoundException extends \Exception
{

    /**
     * NoNotificationProcessorFoundException constructor.
     * @param string $notificationId
     */
    public function __construct(string $notificationId)
    {
        parent::__construct("No Notification Processor could be found to process notification with ID " .
            $notificationId);
    }
}
