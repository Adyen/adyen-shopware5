<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Models\Payment\PaymentMethod;

interface PaymentAttributeWriterInterface
{
    public function __invoke(int $paymentMeanId, PaymentMethod $adyenPaymentMethod): void;
}
