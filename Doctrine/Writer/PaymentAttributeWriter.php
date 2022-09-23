<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Models\Payment\PaymentMethod;
use AdyenPayment\Shopware\Crud\AttributeWriterInterface;
use Shopware\Bundle\AttributeBundle\Service\DataPersister;
use Shopware\Bundle\AttributeBundle\Service\TypeMapping;

final class PaymentAttributeWriter implements PaymentAttributeWriterInterface
{
    /**
     * Do NOT use the DataPersisterInterface since it does not exist in Shopware 5.6.0.
     */
    /** @var DataPersister */
    private $dataPersister;

    /**
     * Do NOT use the AttributeWriterInterfaceInterface since it does not exist in Shopware 5.6.0.
     */
    /** @var AttributeWriterInterface */
    private $attributeUpdater;

    public function __construct(DataPersister $dataPersister, AttributeWriterInterface $attributeUpdater)
    {
        $this->dataPersister = $dataPersister;
        $this->attributeUpdater = $attributeUpdater;
    }

    public function __invoke(int $paymentMeanId, PaymentMethod $adyenPaymentMethod): void
    {
        $attributesColumns = [AdyenPayment::ADYEN_CODE => TypeMapping::TYPE_STRING];

        $dataPersister = $this->dataPersister;
        $this->attributeUpdater->writeReadOnlyAttributes(
            $table = 's_core_paymentmeans_attributes',
            $attributesColumns,
            static function() use ($dataPersister, $table, $paymentMeanId, $adyenPaymentMethod) {
                return $dataPersister->persist(
                    [
                        '_table' => $table,
                        '_foreignKey' => $paymentMeanId,
                        AdyenPayment::ADYEN_CODE => $adyenPaymentMethod->code(),
                    ],
                    's_core_paymentmeans_attributes',
                    $paymentMeanId
                );
            }
        );
    }
}
