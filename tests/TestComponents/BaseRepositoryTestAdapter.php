<?php

namespace AdyenPayment\Tests\TestComponents;

use Adyen\Core\Tests\Infrastructure\ORM\AbstractGenericStudentRepositoryTest;
use Doctrine\ORM\EntityManager;
use AdyenPayment\Bootstrap\Bootstrap;
use AdyenPayment\Tests\TestComponents\Components\TestBaseRepository;
use AdyenPayment\Tests\TestComponents\Components\TestDatabase;

class BaseRepositoryTestAdapter extends AbstractGenericStudentRepositoryTest
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
     * @inheritDoc
     */
    public function getStudentEntityRepositoryClass(): string
    {
        return TestBaseRepository::class;
    }

    /**
     * @inheritDoc
     */
    public function cleanUpStorage(): void
    {
        $database = new TestDatabase($this->entityManager);
        $database->uninstall();
        $this->entityManager->clear();
    }
}
