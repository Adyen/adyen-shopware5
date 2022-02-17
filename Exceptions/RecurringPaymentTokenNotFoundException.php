<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

use AdyenPayment\Models\PaymentResultCodes;

class RecurringPaymentTokenNotFoundException extends \Exception
{
    public static function withCustomerIdAndOrderNumber(string $customerId, string $orderNumber): self
    {
        return new self(sprintf(
            'Recurring payment token with customer id: %s and order number: %s could not be found.',
            $customerId,
            $orderNumber
        ));
    }

    public static function withPendingResultCodeAndPspReference(string $pspReference): self
    {
        return new self(sprintf(
            'Recurring payment token with result code: %s and psp reference: %s could not be found.',
            PaymentResultCodes::pending()->resultCode(),
            $pspReference
        ));
    }
}
