<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use Shopware\Models\Shop\Shop;

interface PaymentMethodImporterInterface
{
    public function importAll(): \Generator;
    public function importForShop(Shop $shop): \Generator;
}
