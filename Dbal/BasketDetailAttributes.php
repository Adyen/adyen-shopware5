<?php

declare(strict_types=1);

namespace AdyenPayment\Dbal;

use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Zend_Db_Adapter_Exception;

class BasketDetailAttributes
{
    /** @var Enlight_Components_Db_Adapter_Pdo_Mysql */
    private $db;

    public function __construct(Enlight_Components_Db_Adapter_Pdo_Mysql $db)
    {
        $this->db = $db;
    }

    /**
     * @throws Zend_Db_Adapter_Exception
     */
    public function update(int $basketDetailId, array $attributeValues): int
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
    public function insert(int $basketDetailId, array $attributeValues): int
    {
        return $this->db->insert(
            's_order_basket_attributes',
            array_merge($attributeValues, ['basketID' => $basketDetailId])
        );
    }

    public function hasBasketDetails(int $basketDetailId): bool
    {
        return count(
            $this->db
                ->select()
                ->from('s_order_basket_attributes')
                ->where('basketID=?', $basketDetailId)
                ->query()
                ->fetchAll()
        ) > 0;
    }
}
