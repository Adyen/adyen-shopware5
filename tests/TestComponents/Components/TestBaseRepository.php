<?php

namespace AdyenPayment\Tests\TestComponents\Components;

use AdyenPayment\Repositories\BaseRepository;

class TestBaseRepository extends BaseRepository
{
    protected static $doctrineModel = TestEntity::class;
}
