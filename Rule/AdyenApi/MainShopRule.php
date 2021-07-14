<?php

declare(strict_types=1);

namespace AdyenPayment\Rule\AdyenApi;

interface MainShopRule
{
    public function __invoke(int $shopId): bool;
}
