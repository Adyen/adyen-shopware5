<?php

namespace AdyenPayment\Tests;

use Adyen\Core\Infrastructure\ORM\Exceptions\QueryFilterInvalidParamException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use AdyenPayment\Tests\TestComponents\BaseRepositoryTestAdapter;
use Shopware\Tests\Functional\Components\Plugin\TestCase;

class BaseRepositoryWrapperTest extends TestCase
{
    protected static $ensureLoadedPlugins = [
        'AdyenPayment' => [],
    ];
    /**
     * @var BaseRepositoryTestAdapter
     */
    protected $baseTest;

    /**
     * BaseRepositoryWrapperTest constructor.
     *
     * @throws \Exception
     */
    public function __construct()
    {
        parent::__construct(...func_get_args());
        $this->baseTest = new BaseRepositoryTestAdapter(...func_get_args());
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
        if (method_exists($this->baseTest, $name) && is_callable([$this->baseTest, $name])) {
            $this->baseTest->$name(...$arguments);
        }
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    public function testRegisteredRepositories(): void
    {
        $this->baseTest->testRegisteredRepositories();
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    public function testStudentMassInsert(): void
    {
        $this->baseTest->testStudentMassInsert();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testStudentUpdate(): void
    {
        $this->baseTest->testStudentUpdate();
    }

    /**
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryAllStudents(): void
    {
        $this->baseTest->testQueryAllStudents();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersString(): void
    {
        $this->baseTest->testQueryWithFiltersString();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersInt(): void
    {
        $this->baseTest->testQueryWithFiltersInt();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithOr(): void
    {
        $this->baseTest->testQueryWithOr();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithAndAndOr(): void
    {
        $this->baseTest->testQueryWithAndAndOr();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithNotEquals(): void
    {
        $this->baseTest->testQueryWithNotEquals();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithGreaterThan(): void
    {
        $this->baseTest->testQueryWithGreaterThan();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithLessThan(): void
    {
        $this->baseTest->testQueryWithLessThan();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithGreaterEqualThan(): void
    {
        $this->baseTest->testQueryWithGreaterEqualThan();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithLessOrEqualThan(): void
    {
        $this->baseTest->testQueryWithLessOrEqualThan();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithCombinedComparisonOperators(): void
    {
        $this->baseTest->testQueryWithCombinedComparisonOperators();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithInOperator(): void
    {
        $this->baseTest->testQueryWithInOperator();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithNotInOperator(): void
    {
        $this->baseTest->testQueryWithNotInOperator();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithLikeOperator(): void
    {
        $this->baseTest->testQueryWithLikeOperator();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndSort(): void
    {
        $this->baseTest->testQueryWithFiltersAndSort();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithUnknownFieldSort(): void
    {
        $this->baseTest->testQueryWithUnknownFieldSort();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithUnIndexedFieldSort(): void
    {
        $this->baseTest->testQueryWithUnIndexedFieldSort();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithIdFieldSort(): void
    {
        $this->baseTest->testQueryWithIdFieldSort();
    }

    /**
     * @throws QueryFilterInvalidParamException
     * @throws RepositoryNotRegisteredException
     */
    public function testQueryWithFiltersAndLimit(): void
    {
        $this->baseTest->testQueryWithFiltersAndLimit();
    }

    /**
     * @inheritDoc
     *
     * @throws RepositoryClassException
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
