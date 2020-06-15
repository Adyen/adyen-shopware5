//

Ext.define('Shopware.apps.AdyenPaymentOrder.view.detail.tabs.notifications.Detail', {
    extend: 'Ext.form.Panel',
    layout: 'anchor',

    initComponent: function () {
        var me = this;

        me.items = me.getItems();
        me.callParent(arguments);

        if (me.store && me.store.first()) {
            me.loadRecord(me.store.first());
        }
    },

    getItems: function () {
        return [Ext.create('Ext.container.Container', {
            columnWidth: 0.5,
            padding: 10,
            defaults: {
                xtype: 'displayfield',
                labelWidth: 155
            },
            items: [
                { name: 'pspReference', fieldLabel: 'PSP Reference'},
                { name: 'createdAt', fieldLabel: 'Created at'},
                { name: 'updatedAt', fieldLabel: 'Updated at'},
                { name: 'eventCode', fieldLabel: 'Event code'},
                { name: 'merchantAccountCode', fieldLabel: 'Merchant'},
                { name: 'paymentMethod', fieldLabel: 'Payment method'},
                { name: 'amountValue', fieldLabel: 'Amount'},
                { name: 'amountCurrency', fieldLabel: 'Currency'},
                { name: 'status', fieldLabel: 'Status'},
                { name: 'success', fieldLabel: 'Success'},
                { name: 'errorDetails', fieldLabel: 'Error Details'},
            ]
        })];
    }
});