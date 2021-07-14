<?php

declare(strict_types=1);

namespace AdyenPayment\Rule\AdyenApi;

use Doctrine\Common\Persistence\ObjectRepository;

final class UsedMainShopConfigRule implements MainShopRule
{
    /**
     * @var UsedFallbackConfigRuleInterface
     */
    private $usedFallbackConfigRule;

    public function __construct(
        UsedFallbackConfigRuleInterface $usedFallbackConfigRule
    ) {
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
