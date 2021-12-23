<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal\Remover;

use Enlight_Components_Db_Adapter_Pdo_Mysql;

final class PaymentMeanSubShopRemover implements PaymentMeanSubShopRemoverInterface
{
    private Enlight_Components_Db_Adapter_Pdo_Mysql $db;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    public function removeBySubShopId(int $subShopId): void
    {
        $this->db->executeQuery('DELETE FROM s_core_paymentmeans_subshops WHERE subshopID = :subShopId;', [
            ':subShopId' => $subShopId,
        ]);
    }
}
