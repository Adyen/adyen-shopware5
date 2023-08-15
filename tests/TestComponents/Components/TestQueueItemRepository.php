<?php

namespace AdyenPayment\Tests\TestComponents\Components;

use AdyenPayment\Repositories\QueueItemRepository;

class TestQueueItemRepository extends QueueItemRepository
{
    /**
     * Fully qualified name of this class.
     */
    public const THIS_CLASS_NAME = __CLASS__;

    protected static $doctrineModel = TestEntity::class;

    /**
     * Retrieves db_name for DBAL.
     *
     * @return string
     */
    protected function getDbName(): string
    {
        return 'test_adyen_entity';
    }
}
