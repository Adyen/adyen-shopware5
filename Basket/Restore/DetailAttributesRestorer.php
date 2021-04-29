<?php


namespace AdyenPayment\Basket\Restore;


use Enlight_Components_Db_Adapter_Pdo_Mysql;
use Shopware\Components\Model\ModelManager;
use Zend_Db_Adapter_Exception;

class DetailAttributesRestorer
{
    /**
     * @var Enlight_Components_Db_Adapter_Pdo_Mysql
     */
    private $db;

    /**
     * @var ModelManager
     */
    private $modelManager;

    public function __construct(
        Enlight_Components_Db_Adapter_Pdo_Mysql $db,
        ModelManager $modelManager
    ) {
        $this->db = $db;
        $this->modelManager = $modelManager;
    }

    /**
     * Copies attributes from the supplied order detail article ID to a basket detail ID
     *
     * @param int $orderDetailId
     * @param int $basketDetailId
     * @throws Zend_Db_Adapter_Exception
     */
    public function restore(int $orderDetailId, int $basketDetailId)
    {
        // Getting all attributes from order detail
        $orderDetailAttributesResult = $this->db
            ->select()
            ->from('s_order_details_attributes')
            ->where('detailID=?', $orderDetailId)
            ->query()
            ->fetchAll();
        $orderDetailAttributes = $orderDetailAttributesResult[0];

        // Getting order attributes columns to possibly fill
        $basketAttributesColumns = $this->modelManager
            ->getClassMetadata('Shopware\Models\Attribute\OrderDetail')
            ->getColumnNames();

        // These columns shouldn't be translated from the order detail to the basket detail
        $columnsToSkip = [
            'id',
            'detailID'
        ];
        $attributes = array_diff($basketAttributesColumns, $columnsToSkip);

        // Updating the basket attributes with the order attribute values
        $attributeValues = [];
        foreach ($attributes as $attribute) {
            if (!empty($orderDetailAttributes[$attribute])) {
                $attributeValues[$attribute] = $orderDetailAttributes[$attribute];
            }
        }
        if (count($attributeValues) > 0) {
            // Check if there's a s_order_basket_attributes row to update or if it needs to be inserted
            $basketDetailsRow = $this->db
                ->select()
                ->from('s_order_basket_attributes')
                ->where('basketID=?', $basketDetailId)
                ->query()
                ->fetchAll();

            if (count($basketDetailsRow) > 0) {
                $this->db->update(
                    's_order_basket_attributes',
                    $attributeValues,
                    ['basketID = ?' => $basketDetailId]
                );
            } else {
                $this->db->insert(
                    's_order_basket_attributes',
                    array_merge($attributeValues, ['basketID' => $basketDetailId])
                );
            }
        }
    }
}
