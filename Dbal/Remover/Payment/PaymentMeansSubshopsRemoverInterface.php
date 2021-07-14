<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Remover\Payment;

interface PaymentMeansSubshopsRemoverInterface
{
    public function deleteAllAdyenPaymentMethodsForSubshop(int $subshopId);
}
