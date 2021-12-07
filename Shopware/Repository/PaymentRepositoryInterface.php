<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Repository;

use Shopware\Models\Payment\Payment;

interface PaymentRepositoryInterface
{
    public function existsByName(string $name): bool;

    public function existsDuplicate(Payment $newPayment): bool;
}
