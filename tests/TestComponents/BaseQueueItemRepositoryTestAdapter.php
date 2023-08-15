<?php

namespace AdyenPayment\Tests\TestComponents;

use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryClassException;
use Adyen\Core\Infrastructure\ORM\Exceptions\RepositoryNotRegisteredException;
use Adyen\Core\Infrastructure\TaskExecution\Exceptions\TaskRunnerStatusStorageUnavailableException;
use Adyen\Core\Tests\Infrastructure\ORM\AbstractGenericQueueItemRepositoryTest;
use Doctrine\Common\Persistence\Mapping\MappingException;
use Doctrine\ORM\EntityManager;
use Adyen\Core\Infrastructure\ORM\RepositoryRegistry;
use Adyen\Core\Infrastructure\TaskExecution\Interfaces\Priority;
use AdyenPayment\Bootstrap\Bootstrap;
use AdyenPayment\Tests\TestComponents\Components\TestDatabase;
use AdyenPayment\Tests\TestComponents\Components\TestQueueItemRepository;

class BaseQueueItemRepositoryTestAdapter extends AbstractGenericQueueItemRepositoryTest
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * Sets entity manager.
     *
     * @param EntityManager $entityManager
     */
    public function setEntityManager(EntityManager $entityManager): void
    {
        $this->entityManager = $entityManager;
    }

    /**
     * @inheritDoc
     * @throws TaskRunnerStatusStorageUnavailableException
     */
    public function setUp(): void
    {
        $database = new TestDatabase($this->entityManager);
        $database->install();

        Bootstrap::init();

        parent::setUp();
    }

    /**
     * @inheritDoc
     */
    public function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * @return string
     */
    public function getQueueItemEntityRepositoryClass(): string
    {
        return TestQueueItemRepository::getClassName();
    }

    /**
     * Cleans up all storage services used by repositories
     * @throws RepositoryNotRegisteredException
     * @throws MappingException
     */
    public function cleanUpStorage(): void
    {
        $database = new TestDatabase($this->entityManager);
        $database->uninstall();
        $this->entityManager->clear();
    }
}
