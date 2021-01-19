<?php

declare(strict_types=1);

namespace AdyenPayment\Http\Validator\Notification;

use AdyenPayment\Exceptions\AuthorizationException;

interface NotificationValidatorInterface
{
    /**
     * @throws AuthorizationException and derived exceptions
     */
    public function validate(array $notifications);
}
