<?php

declare(strict_types=1);

namespace AdyenPayment\Basket\Restore;

use AdyenPayment\Dbal\BasketDetailAttributes;
use AdyenPayment\Dbal\OrderDetailAttributes;
use Shopware\Components\Model\ModelManager;
use Zend_Db_Adapter_Exception;

class DetailAttributesRestorer
{
    /** @var BasketDetailAttributes */
    private $basketDetailAttributes;

    /** @var ModelManager */
    private $modelManager;

    /** @var OrderDetailAttributes */
    private $orderDetailAttributes;

    public function __construct(
        ModelManager $modelManager,
        BasketDetailAttributes $basketDetailAttributes,
        OrderDetailAttributes $orderDetailAttributes
    ) {
        $this->modelManager = $modelManager;
        $this->basketDetailAttributes = $basketDetailAttributes;
        $this->orderDetailAttributes = $orderDetailAttributes;
    }

    /**
     * Copies attributes from the supplied order detail article ID to a basket detail ID.
     *
     * @throws Zend_Db_Adapter_Exception
     */
    public function restore(int $orderDetailId, int $basketDetailId): void
    {
        $orderDetailAttributes = $this->orderDetailAttributes->fetchByOrderDetailId((string) $orderDetailId);
        if (!count($orderDetailAttributes)) {
            return;
        }

        $attributes = $this->provideFillableAttributeColumns();

        if (!count($attributes)) {
            return;
        }

        // Updating the basket attributes with the order attribute values
        $attributeValues = [];
        foreach ($attributes as $attribute) {
            if (!empty($orderDetailAttributes[$attribute])) {
                $attributeValues[$attribute] = $orderDetailAttributes[$attribute];
            }
        }

        if (!count($attributeValues)) {
            return;
        }

        $this->basketDetailAttributes->hasBasketDetails($basketDetailId)
            ? $this->basketDetailAttributes->update($basketDetailId, $attributeValues)
            : $this->basketDetailAttributes->insert($basketDetailId, $attributeValues);
    }

    private function provideFillableAttributeColumns(): array
    {
        // Getting order attributes columns to possibly fill
        $basketAttributesColumns = $this->modelManager
            ->getClassMetadata('Shopware\Models\Attribute\OrderDetail')
            ->getColumnNames();

        // These columns shouldn't be translated from the order detail to the basket detail
        $columnsToSkip = [
            'id',
            'detailID',
        ];

        return array_diff($basketAttributesColumns, $columnsToSkip);
    }
}
