<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Enum;

/**
 * Class PaymentResultCodes.
 */
class PaymentResultCodes
{
    public const AUTHORISED = 'Authorised';
    public const PENDING = 'Pending';
    public const RECEIVED = 'Received';
    public const CANCELLED = 'Cancelled';
    public const ERROR = 'Error';
    public const REFUSED = 'Refused';
}
