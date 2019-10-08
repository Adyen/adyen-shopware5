
// {namespace name=backend/adyen/notification/listing}
Ext.define('Shopware.apps.MeteorAdyenNotificationsListingExtension.view.list.Notification', {
    extend: 'Shopware.grid.Panel',
    alias:  'widget.product-listing-grid',
    region: 'center',

    configure: function() {
        return {
            addButton: false,
            deleteButton: false
        };
    },


    /**
     * Contains all snippets for the view component
     * @object
     */
    snippets:{
        columns: {
            pspReference:'{s name=column/pspReference}PSP Reference{/s}',
            createdAt:'{s name=column/createdAt}Created at{/s}',
            updatedAt:'{s name=column/updatedAt}Updated at{/s}',
            status:'{s name=column/status}Status{/s}',
            eventCode:'{s name=column/eventCode}Event Code{/s}',
            success:'{s name=column/success}Success{/s}',
            merchantAccountCode:'{s name=column/merchantAccountCode}Merchant Account Code{/s}',
            amountValue:'{s name=column/amountValue}Amount Value{/s}',
            amountCurrency:'{s name=column/amountCurrency}Amount Currency{/s}',
            errorDetails:'{s name=column/errorDetails}Error Details{/s}',
            orderDetails:'{s name=column/orderDetails}Order Details{/s}',
        },
        successTitle: '{s name=message/save/success_title}Successful{/s}',
        failureTitle: '{s name=message/save/error_title}Error{/s}',
        orderDoesNotExistAnymore: '{s name=order_does_not_exist_anymore}This order does not exist anymore{/s}',
    },

    /**
     * Creates the grid columns
     *
     * @return [array] grid columns
     */
    getColumns:function () {
        var me = this;

        return [
            {
                header: me.snippets.columns.pspReference,
                dataIndex: 'pspReference',
                flex: 1
            },
            {
                header: me.snippets.columns.createdAt,
                dataIndex: 'createdAt',
                flex: 1
            },
            {
                header: me.snippets.columns.updatedAt,
                dataIndex: 'updatedAt',
                flex: 1
            },
            {
                header: me.snippets.columns.status,
                dataIndex: 'status',
                flex: 1
            },
            {
                header: me.snippets.columns.eventCode,
                dataIndex: 'eventCode',
                flex: 1
            },
            {
                header: me.snippets.columns.success,
                dataIndex: 'success',
                flex: 1
            },
            {
                header: me.snippets.columns.merchantAccountCode,
                dataIndex: 'merchantAccountCode',
                flex: 1
            },
            {
                header: me.snippets.columns.amountValue,
                dataIndex: 'amountValue',
                flex: 1
            },
            {
                header: me.snippets.columns.amountCurrency,
                dataIndex: 'amountCurrency',
                flex: 1
            },
            {
                header: me.snippets.columns.errorDetails,
                dataIndex: 'errorDetails',
                flex: 1
            },
            me.createActionColumn()
        ];
    },

    createActionColumn: function() {
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
