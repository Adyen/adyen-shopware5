// {namespace name=backend/adyen/configuration}

//{block name="backend/order/view/list/list"}
// {$smarty.block.parent}

Ext.define('Shopware.apps.Adyen.view.Order.List', {
    override: 'Shopware.apps.Order.view.list.List',
    getColumns: function () {
        let result = this.callParent();

        result = this.addAdyenColumns(result);

        return result;
    },
    addAdyenColumns: function (columns) {
        let i = 0;
        for (i; i < columns.length; i++) {
            if (columns[i].dataIndex === 'number') {
                break;
            }
        }

        columns.splice(i + 1, 0, {
            header: '{s name="order/adyen/psp"}Adyen PSP reference{/s}',
            dataIndex: 'adyenPspReference',
            flex: 4,
            sortable: false,
            renderer: renderAdyenPspReferenceColumn
        });

        columns.splice(i + 2, 0, {
            header: '{s name="order/adyen/paymentMethod"}Adyen payment method{/s}',
            dataIndex: 'adyenPaymentMethod',
            flex: 1,
            sortable: false,
            renderer: renderPrintLabelsColumn
        });

        return columns;

        function renderAdyenPspReferenceColumn(value, meta, model) {
            return model.get('adyenPspReference');
        }

        function renderPrintLabelsColumn(value, meta, model) {
            return model.get('adyenPaymentMethod');
        }
    }
});

//{/block}
