<?php

namespace AdyenPayment\Tests\TestComponents\Components;

use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Tools\SchemaTool;

class TestDatabase
{
    /**
     * @var EntityManager
     */
    private $entityManager;
    /**
     * @var SchemaTool
     */
    private $schemaTool;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
        $this->schemaTool = new SchemaTool($this->entityManager);
    }
    public function install(): void
    {
        $this->schemaTool->updateSchema($this->getClassesMetaData(), true);
    }

    public function uninstall(): void
    {
        $this->schemaTool->dropSchema($this->getClassesMetaData());
    }

    protected function getClassesMetaData(): array
    {
        return [
            $this->entityManager->getClassMetadata(TestEntity::class)
        ];
    }
}
