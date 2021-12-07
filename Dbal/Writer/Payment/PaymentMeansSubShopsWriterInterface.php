<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Writer\Payment;

interface PaymentMeansSubShopsWriterInterface
{
    public function registerAdyenPaymentMethodForSubShop(int $subShopId);
}
