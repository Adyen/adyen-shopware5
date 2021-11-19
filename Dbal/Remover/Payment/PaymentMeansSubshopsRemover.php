<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Remover\Payment;

use Enlight_Components_Db_Adapter_Pdo_Mysql;

final class PaymentMeansSubshopsRemover implements PaymentMeansSubshopsRemoverInterface
{
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    public function removeBySubShopId(int $subshopId): void
    {
        $this->db->executeQuery(
            'DELETE FROM s_core_paymentmeans_subshops
                    WHERE subshopID = :subshopID;',
            [
                ':subshopID' => $subshopId,
            ]
        );
    }
}
