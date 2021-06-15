<?php

declare(strict_types=1);

namespace AdyenPayment\Rule\AdyenApi;

use Doctrine\Common\Persistence\ObjectRepository;

class UsedMainShopConfigRule implements MainShopRule
{
    /**
     * @var UsedFallbackConfigRule
     */
    private $usedFallbackConfigRule;

    public function __construct(
        UsedFallbackConfigRule $usedFallbackConfigRule
    )
    {
        $this->usedFallbackConfigRule = $usedFallbackConfigRule;
    }

    public function __invoke(int $shopId): bool
    {
        if (1 === $shopId) {
            return true;
        }

        return ($this->usedFallbackConfigRule)($shopId);
    }
}
