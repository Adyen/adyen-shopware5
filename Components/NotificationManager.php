<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use AdyenPayment\Models\Enum\NotificationStatus;
use AdyenPayment\Models\Notification;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Doctrine\Persistence\ObjectRepository;
use Shopware\Components\Model\ModelManager;

/**
 * Class NotificationManager.
 */
class NotificationManager
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var EntityRepository|ObjectRepository
     */
    private $notificationRepository;

    /**
     * NotificationManager constructor.
     */
    public function __construct(
        ModelManager $modelManager
    ) {
        $this->modelManager = $modelManager;
        $this->notificationRepository = $modelManager->getRepository(Notification::class);
    }

    /**
     * @throws NoResultException
     * @throws NonUniqueResultException
     */
    public function getNextNotificationToHandle()
    {
        $builder = $this->notificationRepository->createQueryBuilder('n');
        $builder->where('n.status = :statusReceived OR n.status = :statusRetry')
            ->andWhere('(n.scheduledProcessingTime <= :processingTime OR n.scheduledProcessingTime IS NULL)')
            ->orderBy('n.updatedAt', 'ASC')
            ->setParameter('statusReceived', NotificationStatus::STATUS_RECEIVED)
            ->setParameter('statusRetry', NotificationStatus::STATUS_RETRY)
            ->setParameter('processingTime', new \DateTimeImmutable())
            ->setMaxResults(1);

        return $builder->getQuery()->getSingleResult();
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return mixed|null
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
            return;
        }
    }

    /**
     * @throws NonUniqueResultException
     *
     * @return mixed|null
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
            return;
        }
    }

    public function notificationExists(Notification $notification): bool
    {
        return $this->fetchNotification($notification) instanceof Notification;
    }

    public function fetchNotification(Notification $notification): ?Notification
    {
        return $this->notificationRepository->findOneBy([
            'orderId' => $notification->getOrderId(),
            'pspReference' => $notification->getPspReference(),
            'paymentMethod' => $notification->getPaymentMethod(),
            'success' => $notification->isSuccess(),
            'eventCode' => $notification->getEventCode(),
            'merchantAccountCode' => $notification->getMerchantAccountCode(),
            'amountValue' => $notification->getAmountValue(),
            'amountCurrency' => $notification->getAmountCurrency(),
        ]);
    }
}
