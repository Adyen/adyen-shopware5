<?php

namespace AdyenPayment\E2ETest\Repositories;

use Shopware\Models\Shop\Repository;
use Shopware\Models\Shop\Shop;

/**
 * Class ShopRepository
 *
 * @package AdyenPayment\E2ETest\Repositories
 */
class ShopRepository
{
    /**
     * Shopware\Models\User\Repository
     *
     * @var Repository
     */
    private $shopwareRepository;

    /**
     * ShopRepository constructor.
     */
    public function __construct()
    {
        $this->shopwareRepository = Shopware()->Models()->getRepository(Shop::class);
    }

    /**
     * Returns host from default shop in database
     *
     * @return string|null
     */
    public function getDefaultShopHost(): ?string
    {
        return $this->shopwareRepository->getDefault()->getHost();
    }
}
