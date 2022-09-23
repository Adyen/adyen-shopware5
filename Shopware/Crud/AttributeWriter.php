<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Crud;

use Shopware\Bundle\AttributeBundle\Service\CrudService;
use Shopware\Components\Model\ModelManager;

final class AttributeWriter implements AttributeWriterInterface
{
    /**
     * Do NOT use the CrudServiceInterface since it does not exist in Shopware 5.6.0.
     */
    /** @var CrudService */
    private $crudService;

    /** @var ModelManager */
    private $entityManager;

    public function __construct(CrudService $crudService, ModelManager $entityManager)
    {
        $this->crudService = $crudService;
        $this->entityManager = $entityManager;
    }

    public function writeReadOnlyAttributes(string $attributeTable, array $columns, callable $writer): void
    {
        $this->updateReadonlyOnAttributes($attributeTable, $columns, false);
        $writer();
        $this->updateReadonlyOnAttributes($attributeTable, $columns, true);
    }

    private function updateReadonlyOnAttributes(string $attributeTable, array $columns, bool $readOnly): void
    {
        foreach ($columns as $column => $columnType) {
            $this->crudService->update($attributeTable, $column, $columnType, ['readonly' => $readOnly]);
        }
        $this->rebuildAttributeModel($attributeTable);
    }

    private function rebuildAttributeModel(string $attributeTable): void
    {
        $metaDataCache = $this->entityManager->getConfiguration()->getMetadataCacheImpl();
        if ($metaDataCache) {
            $metaDataCache->deleteAll();
        }

        $this->entityManager->generateAttributeModels([$attributeTable]);
    }
}
