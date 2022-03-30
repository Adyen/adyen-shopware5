<?php

declare(strict_types=1);

namespace AdyenPayment\Recurring;

use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;

interface RecurringTokenFactoryInterface
{
    public static function create(array $data): RecurringPaymentToken;
}
