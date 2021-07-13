<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Writer\Payment;

interface PaymentMeanWriterInterface
{
    public function updateAdyenPaymentMethodBySubshopId(int $subshopId);
}
