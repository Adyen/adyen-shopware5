<?php

namespace AdyenPayment\Repositories\Wrapper;

use Shopware\Models\Payment\Repository;

/**
 * Class PaymentMeanRepository
 *
 * @package AdyenPayment\Repositories\Wrapper
 */
class PaymentMeanRepository
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
     * @return array
     */
    public function getAdyenPaymentMeans(): array
    {
        $query = $this->shopwareRepository->createQueryBuilder('payment');
        $query->where('payment.name LIKE :paymentName')->setParameter(':paymentName', 'adyen_%');

        return $query->getQuery()->getArrayResult();
    }
}
