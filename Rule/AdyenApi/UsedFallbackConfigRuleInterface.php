<?php

declare(strict_types=1);

namespace AdyenPayment\Rule\AdyenApi;

interface UsedFallbackConfigRuleInterface
{
    public function __invoke(int $shopId): bool;
}
