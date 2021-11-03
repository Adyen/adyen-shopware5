<?php

declare(strict_types=1);

namespace AdyenPayment\Components;

use AdyenPayment\Models\TextNotification;
use Doctrine\Persistence\ObjectRepository;
use Doctrine\ORM\EntityRepository;
use Shopware\Components\Model\ModelManager;

/**
 * Class NotificationManager
 * @package AdyenPayment\Components
 */
class TextNotificationManager
{
    /**
     * @var ModelManager
     */
    private $modelManager;

    /**
     * @var ObjectRepository|EntityRepository
     */
    private $textNotificationRepository;


    /**
     * NotificationManager constructor.
     * @param ModelManager $modelManager
     */
    public function __construct(
        ModelManager $modelManager
    ) {
        $this->modelManager = $modelManager;
        $this->textNotificationRepository = $modelManager->getRepository(TextNotification::class);
    }

    /**
     * @return array
     */
    public function getTextNextNotificationsToHandle(): array
    {
        $builder = $this->textNotificationRepository->createQueryBuilder('n');
        $builder->orderBy('n.createdAt', 'ASC')->setMaxResults(20);

        return $builder->getQuery()->getResult();
    }
}
