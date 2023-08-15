//{namespace name=backend/adyen/configuration}

Ext.define('Shopware.apps.AdyenTransaction.AdyenOrderDetailList', {
    extend: 'Ext.grid.Panel',
    alias: 'widget.adyen-order-detail-list',
    width: '100%',

    initComponent: function () {
        var me = this;

        me.columns = me.getColumns();

        me.callParent(arguments);
    },

    getColumns: function () {
        return [{
            header: '{s name="payment/adyen/detail/datetime"}Date & time{/s}',
            dataIndex: 'date',
            sortable: false,
            xtype: 'datecolumn',
            format: 'd.m.Y H:i:s',
            flex: 1,
        }, {
            header: '{s name="payment/adyen/detail/eventcode"}Event code{/s}',
            dataIndex: 'eventCode',
            sortable: false,
            flex: 1,
        }, {
            header: '{s name="payment/adyen/detail/status"}Status{/s}',
            dataIndex: 'status',
            sortable: false,
            flex: 1,
        }];
    }
});
