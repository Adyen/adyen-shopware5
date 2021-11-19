<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use AdyenPayment\Models\PaymentMethod\ImportResult;
use Shopware\Models\Shop\Shop;

interface PaymentMethodImporterInterface
{
    /**
     * @return \Generator<ImportResult>|ImportResult[]
     */
    public function importAll(): \Generator;
    /**
     * @return \Generator<ImportResult>|ImportResult[]
     */
    public function importForShop(Shop $shop): \Generator;
}
