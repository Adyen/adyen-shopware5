<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Shopware\Models\Payment\Payment;
use Shopware\Models\Shop\Shop;

interface StoredPaymentMethodWriterInterface
{
    public function __invoke(PaymentMethod $adyenStoredPaymentMethod, Shop $shop): ImportResult;
}
