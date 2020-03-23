//

Ext.define('Shopware.apps.MeteorAdyenOrder.view.detail.tabs.Refunds', {
    extend: 'Ext.container.Container',

    initComponent: function () {
        var me = this;
        me.items = me.createItems();
        me.callParent(arguments);
    },

    createItems: function () {
        var me = this;

        return [
            me.getRefundsDetail()
        ];
    },

    getRefundsDetail: function () {
        var me = this;

        me.detailView = Ext.create('Shopware.apps.MeteorAdyenOrder.view.detail.tabs.refunds.Detail', {
            record: me.record,
            refunds: me,
        });
        return me.detailView;
    }
});