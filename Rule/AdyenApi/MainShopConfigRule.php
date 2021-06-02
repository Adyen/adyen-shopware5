<?php

declare(strict_types=1);

namespace AdyenPayment\Rule\AdyenApi;

use Shopware\Models\Shop\Shop;

interface MainShopConfigRule
{
    public function __invoke(Shop $shop, Shop $mainShop): bool;
}
