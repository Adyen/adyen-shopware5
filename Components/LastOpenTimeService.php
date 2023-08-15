<?php

namespace AdyenPayment\Components;


use Adyen\Core\BusinessLogic\Domain\Multistore\StoreContext;
use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ORM\Interfaces\RepositoryInterface;
use Adyen\Core\Infrastructure\ORM\QueryFilter\Operators;
use Adyen\Core\Infrastructure\ORM\QueryFilter\QueryFilter;
use AdyenPayment\Entities\LastOpenTime;
use DateTime;

/**
 * Class LastOpenTimeService
 *
 * @package AdyenPayment\Components
 */
class LastOpenTimeService
{
    /**
     * @var RepositoryInterface
     */
    private $repository;

    /**
     * @param RepositoryInterface $repository
     */
    public function __construct(RepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param DateTime $dateTime
     *
     * @return void
     */
    public function saveLastOpenTime(DateTime $dateTime): void
    {
        /** @var LastOpenTime $lastOpenTime */
        $lastOpenTime = $this->repository->selectOne();

        if (!$lastOpenTime) {
            $lastOpenTime = new LastOpenTime();
            $lastOpenTime->setTimestamp($dateTime->getTimestamp());
            $this->repository->save($lastOpenTime);

            return;
        }

        $lastOpenTime->setTimestamp($dateTime->getTimestamp());
        $this->repository->update($lastOpenTime);
    }

    /**
     * @return DateTime
     */
    public function getLastOpenTime(): DateTime
    {
        /** @var LastOpenTime $lastOpenTime */
        $lastOpenTime = $this->repository->selectOne();

        return $lastOpenTime ? (new DateTime())->setTimestamp($lastOpenTime->getTimestamp())
            : (new DateTime())->setTimestamp(0);
    }
}
