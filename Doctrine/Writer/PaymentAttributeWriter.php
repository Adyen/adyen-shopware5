<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Shopware\Crud\AttributeWriterInterface;
use Shopware\Bundle\AttributeBundle\Service\DataPersisterInterface;
use Shopware\Bundle\AttributeBundle\Service\TypeMappingInterface;

final class PaymentAttributeWriter implements PaymentAttributeWriterInterface
{
    private DataPersisterInterface $dataPersister;
    private AttributeWriterInterface $attributeUpdater;

    public function __construct(DataPersisterInterface $dataPersister, AttributeWriterInterface $attributeUpdater)
    {
        $this->dataPersister = $dataPersister;
        $this->attributeUpdater = $attributeUpdater;
    }

    public function __invoke(int $paymentMeanId, PaymentMethod $adyenPaymentMethod): void
    {
        $attributesColumns = [
            AdyenPayment::ADYEN_UNIQUE_IDENTIFIER => TypeMappingInterface::TYPE_STRING,
            AdyenPayment::ADYEN_STORED_METHOD_ID => TypeMappingInterface::TYPE_STRING,
        ];

        $dataPersister = $this->dataPersister;
        $this->attributeUpdater->writeReadOnlyAttributes(
            $table = 's_core_paymentmeans_attributes',
            $attributesColumns,
            static fn() => $dataPersister->persist(
                [
                    '_table' => $table,
                    '_foreignKey' => $paymentMeanId,
                    AdyenPayment::ADYEN_UNIQUE_IDENTIFIER => $adyenPaymentMethod->uniqueIdentifier(),
                    AdyenPayment::ADYEN_STORED_METHOD_ID => $adyenPaymentMethod->getStoredPaymentMethodId(),
                ],
                's_core_paymentmeans_attributes',
                $paymentMeanId
            )
        );
    }
}
