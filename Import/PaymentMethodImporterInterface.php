<?php

declare(strict_types=1);

namespace AdyenPayment\Import;

use Shopware\Models\Shop\Shop;

interface PaymentMethodImporterInterface
{
    /**
     * @return \Generator<\AdyenPayment\Models\PaymentMethod\ImportResult>
     */
    public function importAll(): \Generator;
    /**
     * @return \Generator<\AdyenPayment\Models\PaymentMethod\ImportResult>
     */
    public function importForShop(Shop $shop): \Generator;
}
