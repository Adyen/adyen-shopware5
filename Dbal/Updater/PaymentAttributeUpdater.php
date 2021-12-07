<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Updater;

use Shopware\Bundle\AttributeBundle\Service\CrudServiceInterface;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;
use Shopware\Bundle\AttributeBundle\Service\TypeMappingInterface;
use Shopware\Components\Model\ModelManager;

class PaymentAttributeUpdater implements PaymentAttributeUpdaterInterface
{
    /** @var CrudServiceInterface */
    private $crudService;

    /** @var ModelManager */
    private $entityManager;

    public function __construct(
        CrudServiceInterface $crudService,
        ModelManager $entityManager
    ) {
        $this->crudService = $crudService;
        $this->entityManager = $entityManager;
    }

    public function updateReadonlyOnAdyenPaymentAttributes(array $columns, bool $readOnly): void
    {
        foreach ($columns as $column) {
            $this->crudService->update(
                's_core_paymentmeans_attributes',
                $column,
                TypeMappingInterface::TYPE_STRING,
                ['readonly' => $readOnly]
            );
        }

        $this->rebuildPaymentAttributeModel();
    }

    private function rebuildPaymentAttributeModel(): void
    {
        /** @var \Doctrine\Common\Cache\CacheProvider $metaDataCache */
        $metaDataCache = $this->entityManager->getConfiguration()->getMetadataCache();
        if ($metaDataCache) {
            $metaDataCache->deleteAll();
        }

        $this->entityManager->generateAttributeModels(['s_core_paymentmeans_attributes']);
    }
}
