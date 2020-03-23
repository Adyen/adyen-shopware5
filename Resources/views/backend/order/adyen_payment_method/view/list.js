//
//{namespace name=backend/order/main}

//{block name="backend/order/view/list/list"}

    Ext.define('Shopware.apps.Order.MeteorAdyen.view.List', {
        override: 'Shopware.apps.Order.view.list.List',

        getColumns: function () {
            var columns = this.callParent(arguments);
            columns.splice(2, 0, {
                header: 'Adyen payment',
                dataIndex: 'meteor_adyen_order_payment',
                flex:1,
                sortable: false,
                renderer: function(value, metaData, record) {
                    return record.raw.meteor_adyen_order_payment;
                }
            });
            return columns;
        },
    });
//{/block}