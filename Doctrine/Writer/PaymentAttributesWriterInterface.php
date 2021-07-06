<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Models\Payment\PaymentMethod;

interface PaymentAttributesWriterInterface
{
    public function storeAdyenPaymentMethodType(int $paymentMeanId, PaymentMethod $adyenPaymentMethodType);
}
