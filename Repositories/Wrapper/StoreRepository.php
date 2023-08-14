<?php

namespace AdyenPayment\Repositories\Wrapper;

use Doctrine\DBAL\Connection;
use Shopware\Models\Shop\Repository;
use Shopware\Models\Shop\Shop;

/**
 * Class StoreRepository
 *
 * @package AdyenPayment\Repositories\Wrapper
 */
class StoreRepository
{
    /**
     * @var Repository
     */
    private $shopwareRepository;

    /**
     * @param Repository $repository
     */
    public function __construct(Repository $repository)
    {
        $this->shopwareRepository = $repository;
    }

    /**
     * Returns array of sub shops in shop system.
     *
     * @return Shop[]
     */
    public function getShopwareSubShops(): array
    {
        $query = $this->shopwareRepository->createQueryBuilder('shop');
        $query->where('shop.main IS NULL');

        return $query->getQuery()->getResult();
    }

    /**
     * Returns array of all language shops that belong to the provided sub shop ids
     *
     * @param int[] $subShopIds
     * @return Shop[]
     */
    public function getShopwareLanguageShops(array $subShopIds): array
    {
        $query = $this->shopwareRepository
            ->createQueryBuilder('shop')
            ->where('shop.main IN(:shopIds)')
            ->setParameter('shopIds', $subShopIds, Connection::PARAM_INT_ARRAY);

        return $query->getQuery()->getResult();
    }

    /**
     * Returns default store from system.
     *
     * @return Shop|null
     */
    public function getShopwareDefaultShop(): ?Shop
    {
        $query = $this->shopwareRepository->createQueryBuilder('shop');
        $query->where('shop.default = 1');
        $result = $query->getQuery()->getResult();

        return !empty($result) ? $result[0] : null;
    }

    /**
     * @param string $id
     *
     * @return Shop|null
     */
    public function getStoreById(string $id): ?Shop
    {
        $query = $this->shopwareRepository->createQueryBuilder('shop');
        $query->where('shop.id = :storeId')->setParameter(':storeId', $id);

        $result = $query->getQuery()->getResult();

        return !empty($result) ? $result[0] : null;
    }

    /**
     * Retrieves shop theme name.
     *
     * @return array
     */
    public function getShopTheme(): array
    {
        $query = $this->shopwareRepository->createQueryBuilder('shop');
        $query->select(['template.template'])
            ->innerJoin('shop.template', 'template')
            ->where('shop.active = 1')
            ->andWhere('shop.default = 1');

        $result = $query->getQuery()->getArrayResult();

        return !empty($result[0]) ? $result[0] : [];
    }
}
