<?php

declare(strict_types=1);

namespace MeteorAdyen\Components;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use MeteorAdyen\Models\Notification;
use Shopware\Components\Model\ModelManager;

/**
 * Class NotificationManager
 * @package MeteorAdyen\Components
 */
class NotificationManager
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var ObjectRepository|EntityRepository
     */
    private $notificationRepository;


    /**
     * NotificationManager constructor.
     * @param ModelManager $modelManager
     */
    public function __construct(
        ModelManager $modelManager
    ) {
        $this->modelManager = $modelManager;
        $this->notificationRepository = $modelManager->getRepository(Notification::class);
    }

    /**
     * @return mixed
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getNextNotificationToHandle()
    {
        $builder = $this->modelManager->getRepository(Notification::class)->createQueryBuilder('n');
        $builder->where("n.status = 'received' OR n.status = 'retry'")
            ->orderBy('n.updatedAt', 'ASC')
            ->setMaxResults(1);

        return $builder->getQuery()->getSingleResult();
    }

    /**
     * @param int $orderId
     * @return mixed|null
     * @throws NonUniqueResultException
     */
    public function getLastNotificationForOrderId(int $orderId)
    {
        try {
            $lastNotification = $this->notificationRepository->createQueryBuilder('n')
                ->where('n.orderId = :orderId')
                ->setMaxResults(1)
                ->orderBy('n.createdAt', 'ASC')
                ->setParameter('orderId', $orderId)
                ->getQuery()
                ->getSingleResult();
            return $lastNotification;
        } catch (NoResultException $ex) {
            return null;
        }
    }
}
