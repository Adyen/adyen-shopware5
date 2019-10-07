<?php

namespace MeteorAdyen\Components;

use Doctrine\ORM\NoResultException;
use MeteorAdyen\Models\Enum\NotificationStatus;
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
     * NotificationManager constructor.
     * @param ModelManager $modelManager
     */
    public function __construct(
        ModelManager $modelManager
    ) {
        $this->modelManager = $modelManager;
    }

    /**
     * @return Notification
     * @throws \Doctrine\ORM\NonUniqueResultException
     */
    public function getNextNotificationToHandle()
    {
        $builder = $this->modelManager->getRepository(Notification::class)->createQueryBuilder('n');
        $builder->where('n.status = :status')
            ->orderBy('n.id', 'ASC')
            ->setParameter('status', 'received')
            ->setMaxResults(1);

        /** @var Notification $notification */
        $notification = $builder->getQuery()->getSingleResult();

        $builder = $this->modelManager->getRepository(Notification::class)->createQueryBuilder('n')->update();
        $builder->set('n.status', "'" . NotificationStatus::STATUS_HANDLED . "'");
        $builder->getQuery()->execute();

        return $notification;
    }
}
