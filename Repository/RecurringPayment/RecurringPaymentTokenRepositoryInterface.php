<?php

declare(strict_types=1);

namespace AdyenPayment\Repository\RecurringPayment;

use AdyenPayment\Models\RecurringPayment\RecurringPaymentToken;

interface RecurringPaymentTokenRepositoryInterface
{
    public function fetchByCustomerIdAndOrderNumber(string $customerId, string $orderNumber): RecurringPaymentToken;
    public function fetchPendingByPspReference(string $pspReference): RecurringPaymentToken;
    public function update(RecurringPaymentToken $recurringPaymentToken): void;
}
