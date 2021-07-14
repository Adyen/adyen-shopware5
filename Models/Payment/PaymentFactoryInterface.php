<?php

declare(strict_types=1);

namespace AdyenPayment\Models\Payment;

use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

interface PaymentFactoryInterface
{
    public function createFromAdyen(PaymentMethod $adyenPaymentMethod, Shop $shop): Payment;

    public function updateFromAdyen(Payment $payment, PaymentMethod $adyenPaymentMethod, Shop $shop): Payment;
}
