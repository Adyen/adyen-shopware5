<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Provider\Payment;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Payment\Payment;

final class PaymentMeanProvider implements PaymentMeanProviderInterface
{
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;
    /**
     * @var ModelRepository
     */
    private $paymentRepository;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db, ModelRepository $paymentRepository)
    {
        $this->db = $db;
        $this->paymentRepository = $paymentRepository;
    }

    /**
     * @return Payment | null
     */
    public function provideByAdyenType(string $adyenType)
    {
        if ('' === $adyenType) {
            return null;
        }

        $result = $this->db->executeQuery(
            'SELECT paymentmeanID FROM s_core_paymentmeans_attributes WHERE adyen_type = :adyenType',
            [':adyenType' => $adyenType]
        );
        $paymentMeanId = $result->fetchColumn() === '' ? null : (int) $result->fetchColumn();
        if (null === $paymentMeanId) {
            return null;
        }

        return $this->paymentRepository->findOneBy(['id' => $paymentMeanId]);
    }

    public function provideByAdyenStoredPaymentMethodId(string $adyenStoredPaymentMethodId)
    {
        if  ('' === $adyenStoredPaymentMethodId) {
            return null;
        }

        $result = $this->db->executeQuery(
            'SELECT paymentmeanID FROM s_core_paymentmeans_attributes WHERE adyen_stored_method_id = :adyenStoredPaymentMethodId',
            [':adyenStoredPaymentMethodId' => $adyenStoredPaymentMethodId]
        );
        $paymentMeanId = (int) $result->fetchColumn();
        if (!$paymentMeanId) {
            return null;
        }

        return $this->paymentRepository->findOneBy(['id' => $paymentMeanId]);
    }
}
