<?php

declare(strict_types=1);

namespace AdyenPayment\Rule\AdyenApi;

use Doctrine\ORM\EntityRepository;

final class UsedFallbackConfigRule implements UsedFallbackConfigRuleInterface
{
    /** @var EntityRepository */
    private $shopRepository;

    /** @var MainShopConfigRule */
    private $mainShopConfigRuleChain;

    public function __construct(EntityRepository $shopRepository, MainShopConfigRule $mainShopConfigRule)
    {
        $this->shopRepository = $shopRepository;
        $this->mainShopConfigRuleChain = $mainShopConfigRule;
    }

    public function __invoke(int $shopId): bool
    {
        if (1 === $shopId) {
            return false;
        }

        return ($this->mainShopConfigRuleChain)(
            $this->shopRepository->find($shopId),
            $this->shopRepository->find(1)
        );
    }
}
