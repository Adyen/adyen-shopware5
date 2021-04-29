<?php

namespace AdyenPayment\Dbal;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Zend_Db_Adapter_Exception;

class BasketDetailAttributes
{
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    /**
     * @throws Zend_Db_Adapter_Exception
     */
    public function update(string $basketDetailId, array $attributeValues): int
    {
        return $this->db->update(
            's_order_basket_attributes',
            $attributeValues,
            ['basketID = ?' => $basketDetailId]
        );
    }

    /**
     * @throws Zend_Db_Adapter_Exception
     */
    public function insert(string $basketDetailId, array $attributeValues): int
    {
        return $this->db->insert(
            's_order_basket_attributes',
            array_merge($attributeValues, ['basketID' => $basketDetailId])
        );
    }
}
