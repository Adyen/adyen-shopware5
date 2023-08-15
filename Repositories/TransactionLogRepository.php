<?php


namespace AdyenPayment\Repositories;


use Adyen\Core\BusinessLogic\DataAccess\TransactionLog\Contracts\ShopLogsRepository;
use AdyenPayment\Models\TransactionLogEntity;

class TransactionLogRepository extends BaseRepositoryWithConditionalDeletes implements ShopLogsRepository
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;

    protected static $doctrineModel = TransactionLogEntity::class;
}
