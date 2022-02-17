<?php

declare(strict_types=1);

namespace AdyenPayment\Models\RecurringPayment;

interface RecurringPaymentTokenRepositoryInterface
{
    public function save(RecurringPaymentToken $recurringPaymentToken): void;
    public function fetchByCustomerIdAndOrderNumber(string $customerId, string $orderNumber): RecurringPaymentToken;
    public function fetchPendingByPspReference(string $pspReference): RecurringPaymentToken;
    public function update(RecurringPaymentToken $recurringPaymentToken): void;
}
