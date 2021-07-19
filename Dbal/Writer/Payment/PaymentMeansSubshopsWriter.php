<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Writer\Payment;

use AdyenPayment\Models\Enum\PaymentMethod\SourceType;
use Enlight_Components_Db_Adapter_Pdo_Mysql;

final class PaymentMeansSubshopsWriter implements PaymentMeansSubshopsWriterInterface
{
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db
    ) {
        $this->db = $db;
    }

    public function registerAdyenPaymentMethodForSubshop(int $subshopId)
    {
        $this->db->executeQuery(
            'INSERT INTO s_core_paymentmeans_subshops (paymentID, subshopID) 
                    SELECT id as paymentID, :subshopID as subshopID
                    FROM s_core_paymentmeans 
                    WHERE s_core_paymentmeans.source = :adyenSource;',
            [
                ':subshopID' => $subshopId,
                ':adyenSource' => SourceType::adyenType()->getType()
            ]
        );
    }
}
