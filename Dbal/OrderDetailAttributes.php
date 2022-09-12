<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal;

use Enlight_Components_Db_Adapter_Pdo_Mysql;

class OrderDetailAttributes
{
    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    public function fetchByOrderDetailId(string $orderDetailId): array
    {
        $orderDetailAttributesResult = $this->db
            ->select()
            ->from('s_order_details_attributes')
            ->where('detailID=?', $orderDetailId)
            ->query()
            ->fetchAll();

        return $orderDetailAttributesResult[0] ?? [];
    }
}
