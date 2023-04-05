<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use AdyenPayment\Exceptions\DuplicateNotificationException;
use AdyenPayment\Models\Enum\NotificationStatus;
use AdyenPayment\Models\Notification;
use Doctrine\ORM\EntityRepository;
use Doctrine\ORM\NonUniqueResultException;
use Doctrine\ORM\NoResultException;
use Shopware\Components\Model\ModelManager;

/**
 * Class NotificationManager.
 */
class NotificationManager
{
    /** @var ModelManager */
    private $modelManager;

    /** @var EntityRepository */
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
    public function getAuthorisationNotificationForOrderId(int $orderId)
    {
        try {
            $notification = $this->notificationRepository->createQueryBuilder('n')
                ->where('n.orderId = :orderId')
                ->andWhere('n.eventCode = :eventCode')
                ->setMaxResults(1)
                ->orderBy('n.createdAt', 'ASC')
                ->setParameter('orderId', $orderId)
                ->setParameter('eventCode', 'AUTHORISATION')
                ->getQuery()
                ->getSingleResult();

            return $notification;
        } catch (NoResultException $ex) {
            return;
        }
    }

    public function guardDuplicate(Notification $notification): void
    {
        $builder = $this->notificationRepository->createQueryBuilder('n');
        $builder
            ->where('n.orderId = :orderId')
            ->andWhere('n.pspReference = :pspReference')
            ->andWhere('n.paymentMethod = :paymentMethod')
            ->andWhere('n.success = :success')
            ->andWhere('n.eventCode = :eventCode')
            ->andWhere('n.merchantAccountCode = :merchantAccountCode')
            ->andWhere('n.amountValue = :amountValue')
            ->andWhere('n.amountCurrency = :amountCurrency')
            ->setParameter('orderId', $notification->getOrderId())
            ->setParameter('pspReference', $notification->getPspReference())
            ->setParameter('paymentMethod', $notification->getPaymentMethod())
            ->setParameter('success', $notification->isSuccess())
            ->setParameter('eventCode', $notification->getEventCode())
            ->setParameter('merchantAccountCode', $notification->getMerchantAccountCode())
            ->setParameter('amountValue', $notification->getAmountValue())
            ->setParameter('amountCurrency', $notification->getAmountCurrency())
            ->setMaxResults(1);

        if ($this->modelManager->contains($notification) && $notification->getId()) {
            $builder
                ->andWhere('n.id <> :id')
                ->setParameter('id', $notification->getId());
        }

        $record = $builder->getQuery()->getOneOrNullResult();

        if ($record instanceof Notification) {
            throw DuplicateNotificationException::withNotification($record);
        }
    }
}
