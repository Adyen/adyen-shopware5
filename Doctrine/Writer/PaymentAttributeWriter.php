<?php

declare(strict_types=1);

namespace AdyenPayment\Doctrine\Writer;

use AdyenPayment\AdyenPayment;
use AdyenPayment\Import\PaymentAttributeUpdaterInterface;
use AdyenPayment\Models\Payment\PaymentMethod;
use Shopware\Bundle\AttributeBundle\Service\DataPersisterInterface;

final class PaymentAttributeWriter implements PaymentAttributeWriterInterface
{
    /** @var DataPersisterInterface */
    private $dataPersister;
    /** @var PaymentAttributeUpdaterInterface */
    private $paymentAttributeUpdater;

    public function __construct(
        DataPersisterInterface $dataPersister,
        PaymentAttributeUpdaterInterface $paymentAttributeUpdater
    ) {
        $this->dataPersister = $dataPersister;
        $this->paymentAttributeUpdater = $paymentAttributeUpdater;
    }

    public function storeAdyenPaymentMethodType(
        int $paymentMeanId,
        PaymentMethod $adyenPaymentMethod
    ) {
        if ($adyenPaymentMethod->isStoredPayment()) {
            $storedPaymentMethodId = $adyenPaymentMethod->getStoredPaymentMethodId();
        }

        $data = [
            '_table' => "s_core_paymentmeans_attributes",
            '_foreignKey' => $paymentMeanId,
            AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL => $adyenPaymentMethod->getType(),
            AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID => $storedPaymentMethodId,
        ];

        $attributesColumns = [
            AdyenPayment::ADYEN_PAYMENT_METHOD_LABEL,
            AdyenPayment::ADYEN_PAYMENT_STORED_METHOD_ID
        ];

        // update read only "false" to allow model changes
        $this->paymentAttributeUpdater->updateReadonlyOnAdyenPaymentAttributes($attributesColumns, false);

        $this->dataPersister->persist(
            $data,
            "s_core_paymentmeans_attributes",
            $paymentMeanId
        );

        $this->paymentAttributeUpdater->updateReadonlyOnAdyenPaymentAttributes($attributesColumns, true);
    }
}
