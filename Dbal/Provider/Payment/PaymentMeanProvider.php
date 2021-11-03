<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Provider\Payment;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Components\Model\ModelRepository;
use Shopware\Models\Payment\Payment;

final class PaymentMeanProvider implements PaymentMeanProviderInterface
{
    private Enlight_Components_Db_Adapter_Pdo_Mysql $db;
    private ModelRepository $paymentRepository;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db, ModelRepository $paymentRepository)
    {
        $this->db = $db;
        $this->paymentRepository = $paymentRepository;
    }

    public function provideByAdyenType(string $adyenType): ?Payment
    {
        if ('' === $adyenType) {
            return null;
        }

        $result = $this->db->executeQuery(
            'SELECT paymentmeanID FROM s_core_paymentmeans_attributes WHERE adyen_type = :adyenType',
            [':adyenType' => $adyenType]
        );
        $paymentMeanId = (int) $result->fetchColumn();
        if (0 === $paymentMeanId) {
            return null;
        }

        return $this->paymentRepository->findOneBy(['id' => $paymentMeanId]);
    }
}
