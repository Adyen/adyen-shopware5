//
// {namespace name=backend/customer/view/order}

// {block name="backend/customer/view/order/list"}
    Ext.define('Shopware.apps.Customer.AdyenPayment.view.List', {
        override: 'Shopware.apps.Customer.view.order.List',

        getColumns: function () {
            var columns = this.callParent(arguments);
            columns.splice(2, 0, {
                header: 'Adyen payment',
                dataIndex: 'adyen_payment_order_payment',
                flex:1,
                sortable: false,
                renderer: function (value, metaData, record) {
                    return record.raw.adyen_payment_order_payment;
                }
            });
            return columns;
        },
    });
//{/block}