<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Dbal\Updater\PaymentAttributeUpdaterInterface;
use AdyenPayment\Models\Payment\PaymentMethod;
use Shopware\Bundle\AttributeBundle\Service\DataPersisterInterface;

final class PaymentAttributeWriter implements PaymentAttributeWriterInterface
{
    private DataPersisterInterface $dataPersister;
    private PaymentAttributeUpdaterInterface $attributeUpdater;

    public function __construct(
        DataPersisterInterface $dataPersister,
        PaymentAttributeUpdaterInterface $attributeUpdater
    ) {
        $this->dataPersister = $dataPersister;
        $this->attributeUpdater = $attributeUpdater;
    }

    public function __invoke(int $paymentMeanId, PaymentMethod $adyenPaymentMethod): void
    {
        $attributesColumns = [
            AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL,
            AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID,
        ];

        // update read only "false" to allow model changes
        $this->attributeUpdater->updateReadonlyOnAdyenPaymentAttributes($attributesColumns, false);
        $this->dataPersister->persist(
            [
                '_table' => 's_core_paymentmeans_attributes',
                '_foreignKey' => $paymentMeanId,
                AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL => $adyenPaymentMethod->uniqueIdentifier(),
                AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID => $adyenPaymentMethod->getStoredPaymentMethodId(),
            ],
            's_core_paymentmeans_attributes',
            $paymentMeanId
        );
        $this->attributeUpdater->updateReadonlyOnAdyenPaymentAttributes($attributesColumns, true);
    }
}
