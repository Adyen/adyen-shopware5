<?php


namespace AdyenPayment\Repositories;


use Adyen\Core\BusinessLogic\DataAccess\Notifications\Contracts\ShopNotificationRepository;
use AdyenPayment\Models\NotificationsEntity;

class NotificationsRepository extends BaseRepositoryWithConditionalDeletes implements ShopNotificationRepository
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;

    protected static $doctrineModel = NotificationsEntity::class;
}
