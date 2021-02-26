<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use Doctrine\Common\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use AdyenPayment\Models\Enum\NotificationStatus;
use AdyenPayment\Models\Notification;
use Shopware\Components\Model\ModelManager;

/**
 * Class NotificationManager
 * @package AdyenPayment\Components
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
        $builder = $this->notificationRepository->createQueryBuilder('n');
        $builder->where("n.status = :statusReceived OR n.status = :statusRetry")
            ->where('(n.scheduledProcessingTime <= :processingTime OR n.scheduledProcessingTime IS NULL)')
            ->orderBy('n.updatedAt', 'ASC')
            ->setParameter('statusReceived', NotificationStatus::STATUS_RECEIVED)
            ->setParameter('statusRetry', NotificationStatus::STATUS_RETRY)
            ->setParameter('processingTime', new \DateTime())
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

    /**
     * @param string $pspReference
     * @return mixed|null
     * @throws NonUniqueResultException
     */
    public function getLastNotificationForPspReference(string $pspReference)
    {
        try {
            $lastNotification = $this->notificationRepository->createQueryBuilder('n')
                ->where('n.pspReference = :pspReference')
                ->setMaxResults(1)
                ->orderBy('n.createdAt', 'ASC')
                ->setParameter('pspReference', $pspReference)
                ->getQuery()
                ->getSingleResult();
            return $lastNotification;
        } catch (NoResultException $ex) {
            return null;
        }
    }
}
