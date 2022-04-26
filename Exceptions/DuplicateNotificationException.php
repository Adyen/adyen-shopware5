<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

use AdyenPayment\Models\Notification;

final class DuplicateNotificationException extends \RunTimeException
{
    public static function withNotification(Notification $notification): self
    {
        return new self(sprintf(
            'Duplicate notification is not handled. 
            Notification with id: "%s", orderId: "%s", pspReference: "%s", status: "%s", paymentMethod: "%s", 
            eventCode: "%s", success: "%s", merchantAccountCode: "%s", amountValue: "%s", amountCurrency: "%s"',
            $notification->getId(),
            $notification->getOrderId(),
            $notification->getPspReference(),
            $notification->getStatus(),
            $notification->getPaymentMethod(),
            $notification->getEventCode(),
            $notification->isSuccess(),
            $notification->getMerchantAccountCode(),
            $notification->getAmountValue(),
            $notification->getAmountCurrency()
        ));
    }
}
