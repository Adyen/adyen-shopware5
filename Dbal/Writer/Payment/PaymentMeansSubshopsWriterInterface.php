<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Writer\Payment;

interface PaymentMeansSubshopsWriterInterface
{
    public function updateAdyenPaymentMethodBySubshopId(int $subshopId);
}
