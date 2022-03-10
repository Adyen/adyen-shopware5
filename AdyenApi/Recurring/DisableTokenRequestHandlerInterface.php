<?php

declare(strict_types=1);

namespace AdyenPayment\AdyenApi\Recurring;

use AdyenPayment\AdyenApi\Model\ApiResponse;
use Shopware\Models\Shop\Shop;

interface DisableTokenRequestHandlerInterface
{
    public function disableToken(string $recurringTokenId, Shop $shop): ApiResponse;
}
