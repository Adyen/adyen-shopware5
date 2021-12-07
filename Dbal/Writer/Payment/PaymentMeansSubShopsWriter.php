<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Writer\Payment;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Enlight_Components_Db_Adapter_Pdo_Mysql;

final class PaymentMeansSubShopsWriter implements PaymentMeansSubShopsWriterInterface
{
    private Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    public function registerAdyenPaymentMethodForSubShop(int $subShopId): void
    {
        $this->db->executeQuery(
            'REPLACE INTO s_core_paymentmeans_subshops (paymentID, subshopID) 
                    SELECT id as paymentID, :subShopId as subshopID
                    FROM s_core_paymentmeans 
                    WHERE s_core_paymentmeans.source = :adyenSource;',
            [
                ':subShopId' => $subShopId,
                ':adyenSource' => SourceType::adyen()->getType(),
            ]
        );
    }
}
