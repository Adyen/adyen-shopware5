<?php


namespace AdyenPayment\Models\Enum;

/**
 * Class PaymentResultCodes
 * @package AdyenPayment\Models\Enum
 */
class PaymentResultCodes
{
    const AUTHORISED = 'Authorised';
    const PENDING = 'Pending';
    const RECEIVED = 'Received';
    const CANCELLED = 'Cancelled';
    const ERROR = 'Error';
    const REFUSED = 'Refused';
}
