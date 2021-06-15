<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal;

use Enlight_Components_Db_Adapter_Pdo_Mysql;

class PaymentAttributes
{
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    public function fetchByAdyenType(string $adyenType): array
    {
        $paymentAttributesResult = $this->db
            ->select()
            ->from('s_core_paymentmeans_attributes')
            ->where('adyen_type=?', $adyenType)
            ->query()
            ->fetchAll();
        return $paymentAttributesResult[0] ?? [];
    }
}