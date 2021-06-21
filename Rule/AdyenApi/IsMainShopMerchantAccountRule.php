<?php

declare(strict_types=1);

namespace AdyenPayment\Rule\AdyenApi;

use AdyenPayment\Components\Configuration;
use Shopware\Models\Shop\Shop;

final class IsMainShopMerchantAccountRule implements MainShopConfigRule
{
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(Configuration $configuration)
    {
        $this->configuration = $configuration;
    }

    public function __invoke(Shop $shop, Shop $mainShop): bool
    {
        return $this->configuration->getMerchantAccount($mainShop)
            === $this->configuration->getMerchantAccount($shop);
    }
}
