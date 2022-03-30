<?php

declare(strict_types=1);

namespace AdyenPayment\Exceptions;

use AdyenPayment\Models\PaymentResultCode;

final class RecurringPaymentTokenNotFoundException extends \RuntimeException
{
    public static function withCustomerIdAndOrderNumber(string $customerId, string $orderNumber): self
    {
        return new self(sprintf(
            'Recurring payment token not found with customer id: "%s", order number: "%s"',
            $customerId,
            $orderNumber
        ));
    }

    public static function withPendingResultCodeAndPspReference(string $pspReference): self
    {
        return new self(sprintf(
            'Recurring payment token not found with result code: "%s", psp reference: "%s"',
            PaymentResultCode::pending()->resultCode(),
            $pspReference
        ));
    }
}
