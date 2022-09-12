<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use AdyenPayment\Models\TextNotification;
use Doctrine\ORM\EntityRepository;
use Shopware\Components\Model\ModelManager;

/**
 * Class NotificationManager.
 */
class TextNotificationManager
{
    /** @var ModelManager */
    private $modelManager;

    /** @var EntityRepository */
    private $textNotificationRepository;

    /**
     * NotificationManager constructor.
     */
    public function __construct(
        ModelManager $modelManager
    ) {
        $this->modelManager = $modelManager;
        $this->textNotificationRepository = $modelManager->getRepository(TextNotification::class);
    }

    public function getTextNextNotificationsToHandle(): array
    {
        $builder = $this->textNotificationRepository->createQueryBuilder('n');
        $builder->orderBy('n.createdAt', 'ASC')->setMaxResults(20);

        return $builder->getQuery()->getResult();
    }
}
