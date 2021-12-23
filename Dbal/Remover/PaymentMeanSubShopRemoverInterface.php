<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Remover;

interface PaymentMeanSubShopRemoverInterface
{
    public function removeBySubShopId(int $subShopId);
}
