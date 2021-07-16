<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Models\PaymentMethod\ImportResult;
use Shopware\Models\Shop\Shop;

interface PaymentMethodWriterInterface
{
    public function __invoke(PaymentMethod $adyenPaymentMethod, Shop $shop): ImportResult;
}
