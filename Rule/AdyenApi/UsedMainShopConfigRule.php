<?php

declare(strict_types=1);

namespace AdyenPayment\Rule\AdyenApi;

use AdyenPayment\Components\Configuration;
use Doctrine\Common\Persistence\ObjectRepository;

class UsedMainShopConfigRule
{
    /**
     * @var ObjectRepository
     */
    private $shopRepository;
    /**
     * @var Configuration
     */
    private $configuration;

    public function __construct(ObjectRepository $shopRepository, Configuration $configuration)
    {
        $this->shopRepository = $shopRepository;
        $this->configuration = $configuration;
    }

    public function __invoke(int $shopId): bool
    {
        if (1 === $shopId) {
            return true;
        }

        $shop = $this->shopRepository->find($shopId);
        $mainShop = $this->shopRepository->find(1);
        $mainShopApiKey = $this->configuration->getApiKey($mainShop);
        $shopApiKey = $this->configuration->getApiKey($shop);

        if ($shopApiKey !== $mainShopApiKey) {
            return false;
        }

        $mainShopMerchantAccount = $this->configuration->getMerchantAccount($mainShop);
        $shopMerchantAccount = $this->configuration->getMerchantAccount($shop);

        return $shopMerchantAccount === $mainShopMerchantAccount;
    }
}
