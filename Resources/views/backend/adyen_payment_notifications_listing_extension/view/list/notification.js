
// {namespace name=backend/adyen/notification/listing}
Ext.define('Shopware.apps.AdyenPaymentNotificationsListingExtension.view.list.Notification', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.product-listing-grid',
    region: 'center',

    configure: function () {
        return {
            addButton: false,
            deleteButton: false,
            columns: {
                'pspReference': { },
                'createdAt': { },
                'updatedAt': { },
                'status': { },
                'paymentMethod': { },
                'eventCode': { },
                'success': { },
                'merchantAccountCode': { },
                'amountValue': { },
                'amountCurrency': { },
                'errorDetails': { },
                'orderId': {
                    renderer: this.orderIdRenderer
                },
            }
        };
    },

    orderIdRenderer: function (value, styles, row) {
        return row.raw.order.number;
    },


    /**
     * Contains all snippets for the view component
     * @object
     */
    snippets:{
        columns: {
            pspReference:'{s name="column/pspReference"}PSP Reference{/s}',
            createdAt:'{s name="column/createdAt"}Created at{/s}',
            updatedAt:'{s name="column/updatedAt"}Updated at{/s}',
            status:'{s name="column/status"}Status{/s}',
            paymentMethod:'{s name="column/paymentMethod"}Payment method{/s}',
            eventCode:'{s name="column/eventCode"}Event Code{/s}',
            success:'{s name="column/success"}Success{/s}',
            merchantAccountCode:'{s name="column/merchantAccountCode"}Merchant Account Code{/s}',
            amountValue:'{s name="column/amountValue"}Amount Value{/s}',
            amountCurrency:'{s name="column/amountCurrency"}Amount Currency{/s}',
            errorDetails:'{s name="column/errorDetails"}Error Details{/s}',
            orderDetails:'{s name="column/orderDetails"}Order Details{/s}',
        },
        successTitle: '{s name="message/save/success_title"}Successful{/s}',
        failureTitle: '{s name="message/save/error_title"}Error{/s}',
        orderDoesNotExistAnymore: '{s name="order_does_not_exist_anymore"}This order does not exist anymore{/s}',
    },

    createActionColumn: function () {
        var me = this;

        return Ext.create('Ext.grid.column.Action', {
            width: 30,
            items:[
                me.createEditOrderColumn(),
            ]
        });
    },

    createEditOrderColumn: function () {
        var me = this;

        return {
            iconCls: 'sprite-eye',
            action: 'editOrder',
            tooltip: me.snippets.columns.orderDetails,

            handler: function (view, rowIndex, colIndex, item) {
                var store = view.getStore(),
                    record = store.getAt(rowIndex);

                Shopware.app.Application.addSubApplication({
                    name: 'Shopware.apps.Order',
                    action: 'detail',
                    params: {
                        orderId: record.data.orderId
                    }
                });
            }
        }
    },
});
