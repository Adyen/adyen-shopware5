//

Ext.define('Shopware.apps.MeteorAdyenOrder.view.detail.tabs.refunds.Detail', {
    extend: 'Ext.form.Panel',

    layout: {
        type: 'table',
        columns: 2,
    },
    bodyPadding: 10,
    height: '100%',
    autoScroll: true,
    ui: 'footer',

    initComponent: function () {
        var me = this;
        me.items = me.createItems();
        me.dockedItems = me.createDock();
        me.callParent(arguments);

        me.loadRecord(me.record);
        console.log(me.record);
    },

    createItems: function () {
        var me = this,
            fields = [];

        fields.push({
            xtype: 'label',
            text: 'Order amount',
            width: 200
        });
        fields.push({
            xtype: 'displayfield',
            name: 'invoiceAmount',
        });

        fields.push({
            xtype: 'label',
            text: 'Total refund amount',
        });
        fields.push({
            xtype: 'displayfield',
            value: me.record.raw.adyenNotification.amountValue,
        });

        fields.push({
            xtype: 'label',
            text: 'Currency',
        });
        fields.push({
            xtype: 'displayfield',
            value: me.record.raw.adyenNotification.amountCurrency,
        });

        return fields;
    },

    createDock: function () {
        var me = this,
            items = [];

        items.push({
            type: 'button',
            text: 'Full refund',
            cls: 'primary',
            handler: function () {
                me.up('window').setLoading(true);

                Ext.Ajax.request({
                    url: '{url controller="MeteorAdyenRefund" action="refund"}',
                    params: {
                        orderId: me.record.get('id')
                    },
                    success: function (response) {
                        var json = JSON.parse(response.responseText);
                        me.up('window').setLoading(false);
                        Ext.Msg.alert(
                            'Adyen Refund',
                            'A refund with Reference ID '
                            + json.refundReference
                            + ' has been created. Check again in a few minutes to see if it has succeeded.',
                            Ext.emptyFn
                        );
                    }
                });
            }
        });

        return [{
            xtype: 'toolbar',
            dock: 'bottom',
            items: items
        }];
    },
});