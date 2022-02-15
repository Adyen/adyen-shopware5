<?php

declare(strict_types=1);

namespace AdyenPayment\Recurring\Mapper;

use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;

interface RecurringTokenMapperInterface
{
    public function __invoke(array $rawData): RecurringPaymentToken;
}
