<?php

declare(strict_types=1);

namespace AdyenPayment\Rule\AdyenApi;

use Shopware\Models\Shop\Shop;

class MainShopConfigRuleChain implements MainShopConfigRule
{
    /**
     * @var MainShopConfigRule[]
     */
    private $mainShopConfigRules;

    public function __construct(MainShopConfigRule ...$mainShopConfigRules)
    {
        $this->mainShopConfigRules = $mainShopConfigRules;
    }

    public function __invoke(Shop $shop, Shop $mainShop): bool
    {
        foreach ($this->mainShopConfigRules as $rule) {
            if ($rule($shop, $mainShop)) {
                return true;
            }
        }

        return false;
    }
}
