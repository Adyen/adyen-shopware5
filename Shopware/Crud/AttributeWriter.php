<?php

declare(strict_types=1);

namespace AdyenPayment\Shopware\Crud;

use Shopware\Bundle\AttributeBundle\Service\CrudServiceInterface;
use Shopware\Components\Model\ModelManager;

final class AttributeWriter implements AttributeWriterInterface
{
    private CrudServiceInterface $crudService;
    private ModelManager $entityManager;

    public function __construct(CrudServiceInterface $crudService, ModelManager $entityManager)
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
        $metaDataCache = $this->entityManager->getConfiguration()->getMetadataCache();
        if ($metaDataCache) {
            $metaDataCache->clear();
        }

        $this->entityManager->generateAttributeModels([$attributeTable]);
    }
}
