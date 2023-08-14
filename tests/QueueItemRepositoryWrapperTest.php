<?php

namespace AdyenPayment\Tests;

use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Adyen\Core\Infrastructure\TaskExecution\Exceptions\QueueItemSaveException;
use Adyen\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use AdyenPayment\Tests\TestComponents\BaseQueueItemRepositoryTestAdapter;
use Shopware\Tests\Functional\Components\Plugin\TestCase;

class QueueItemRepositoryWrapperTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'AdyenPayment' => []
    ];
    /**
     * @var BaseQueueItemRepositoryTestAdapter
     */
    protected $baseTest;

    /**
     * QueueItemRepositoryWrapperTest constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());
        $this->baseTest = new BaseQueueItemRepositoryTestAdapter(...func_get_args());
        $entityManager = Shopware()->Container()->get('models');
        $this->baseTest->setEntityManager($entityManager);
    }

    /**
     * Proxies method to base test.
     *
     * @param $name
     * @param $arguments
     */
    public function __call($name, $arguments)
    {
        if (is_callable([$this->baseTest, $name])) {
            $this->baseTest->$name(...$arguments);
        }
    }

    /**
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testRegisteredRepositories(): void
    {
        $this->baseTest->testRegisteredRepositories();
    }

    /**
     * @depends testRegisteredRepositories
     *
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueueItemMassInsert(): void
    {
        $this->baseTest->testQueueItemMassInsert();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testUpdate(): void
    {
        $this->baseTest->testQueueItemMassInsert();

        $this->baseTest->testUpdate();
    }

    /**
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryAllQueueItems(): void
    {
        $this->baseTest->testQueryAllQueueItems();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersString(): void
    {
        $this->baseTest->testQueryWithFiltersString();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersInt(): void
    {
        $this->baseTest->testQueryWithFiltersInt();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndSort(): void
    {
        $this->baseTest->testQueryWithFiltersAndSort();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndLimit(): void
    {
        $this->baseTest->testQueryWithFiltersAndLimit();
    }

    /**
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testFindOldestQueuedItems(): void
    {
        $this->baseTest->testFindOldestQueuedItems();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     * @throws QueueItemSaveException
     */
    public function testSaveWithCondition(): void
    {
        $this->expectException(QueueItemSaveException::class);
        $this->baseTest->testSaveWithCondition();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     * @throws QueueItemSaveException
     */
    public function testSaveWithConditionWithNull(): void
    {
        $this->expectException(QueueItemSaveException::class);
        $this->baseTest->testSaveWithConditionWithNull();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryClassException
     * @throws RepositoryNotRegisteredException
     */
    public function testInvalidQueryFilter(): void
    {
        $this->expectException(QueryFilterInvalidParamException::class);
        $this->baseTest->testInvalidQueryFilter();
    }

    /**
     * @inheritDoc
     *
     * @throws RepositoryClassException
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function setUp(): void
    {
        $this->baseTest->setUp();
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        $this->baseTest->tearDown();
    }
}
