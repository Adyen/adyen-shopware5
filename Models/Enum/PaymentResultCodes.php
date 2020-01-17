<?php


namespace MeteorAdyen\Models\Enum;

/**
 * Class PaymentResultCodes
 * @package MeteorAdyen\Models\Enum
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