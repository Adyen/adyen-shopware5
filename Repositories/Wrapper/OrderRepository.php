<?php

namespace AdyenPayment\Repositories\Wrapper;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\OptimisticLockException;
use Shopware\Models\Order\Order as ShopwareOrder;
use Shopware\Models\Order\Repository;

/**
 * Class OrderRepository
 *
 * @package AdyenPayment\Repositories\Wrapper
 */
class OrderRepository
{
    /**
     * @var Repository
     */
    private $shopwareRepository;

    public function __construct(Repository $repository)
    {
        $this->shopwareRepository = $repository;
    }

    /**
     * @return array
     */
    public function getOrderStatuses(): array
    {
        return $this->shopwareRepository->getPaymentStatusQuery()->getArrayResult();
    }

    /**
     * Gets the order numbers for the given list of order temporary ids
     *
     * @param string[] $temporaryIds
     * @return array<string, string> Map of order temporary id to its belonging order number
     */
    public function getOrderNumbersFor(array $temporaryIds): array
    {
        $query = $this->shopwareRepository->createQueryBuilder('orders');
        $query
            ->where('orders.temporaryId IN (:temporaryIds)')
            ->setParameter(':temporaryIds', $temporaryIds, Connection::PARAM_STR_ARRAY);

        /** @var ShopwareOrder[] $result */
        $result = $query->getQuery()->getResult();

        $orderMap = [];
        foreach ($result as $order) {
            $orderMap[$order->getTemporaryId()] = $order->getNumber();
        }

        return $orderMap;
    }

    /**
     * Returns a map of Showpare order instances based on a list of order ids
     *
     * @param string[] $orderIds
     * @return ShopwareOrder[]
     */
    public function getOrdersByIds(array $orderIds): array
    {
        $query = $this->shopwareRepository->createQueryBuilder('orders');
        $query
            ->where('orders.id IN (:orderIds)')
            ->setParameter(':orderIds', $orderIds, Connection::PARAM_INT_ARRAY);

        /** @var ShopwareOrder[] $result */
        $result = $query->getQuery()->getResult();

        $orderMap = [];
        foreach ($result as $order) {
            $orderMap[$order->getId()] = $order;
        }

        return $orderMap;
    }

    /**
     * Returns a map of Showpare order instances based on a list of order numbers
     *
     * @param string[] $orderNumbers
     * @return ShopwareOrder[]
     */
    public function getOrdersByNumbers(array $orderNumbers): array
    {
        $query = $this->shopwareRepository->createQueryBuilder('orders');
        $query
            ->where('orders.number IN (:orderNumbers)')
            ->setParameter(':orderNumbers', $orderNumbers, Connection::PARAM_STR_ARRAY);

        /** @var ShopwareOrder[] $result */
        $result = $query->getQuery()->getResult();

        $orderMap = [];
        foreach ($result as $order) {
            $orderMap[$order->getNumber()] = $order;
        }

        return $orderMap;
    }

    /**
     * @param string $temporaryId
     *
     * @return ShopwareOrder | null
     */
    public function getOrderByTemporaryId(string $temporaryId): ?ShopwareOrder
    {
        $query = $this->shopwareRepository
            ->createQueryBuilder('orders')
            ->andWhere('orders.temporaryId = :temporaryId')
            ->setParameter(':temporaryId', $temporaryId)
            ->orderBy('orders.id', 'DESC');

        $result = $query->getQuery()->getResult();

        return !empty($result) ? $result[0] : null;
    }

    /**
     * @param ShopwareOrder $order
     *
     * @return void
     *
     * @throws OptimisticLockException
     */
    public function updateOrder(ShopwareOrder $order)
    {
        $manager = Shopware()->Models();
        $manager->persist($order);
        $manager->flush();
    }

    /**
     * @param int $id
     *
     * @return ShopwareOrder|null
     */
    public function getOrderById(int $id): ?ShopwareOrder
    {
        $query = $this->shopwareRepository->createQueryBuilder('orders');
        $query->andWhere('orders.id = :id')->setParameter(':id', $id);

        $result = $query->getQuery()->getResult();

        return !empty($result) ? $result[0] : null;
    }
}
