//{block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.AdyenPaymentOrder.view.detail.Window', {
    /**
     * Override the customer detail window
     * @string
     */
    override: 'Shopware.apps.Order.view.detail.Window',

    initComponent: function () {
        var me = this;
        me.callParent();
    },

    /**
     * Overwrite to add adyen transaction tab if necessary
     */
    createTabPanel: function () {
        var me = this,
            result = me.callParent();

        result.add(me.createAdyenTab(!!me.record.raw.adyenTransaction));

        return result;
    },

    /**
     * Generate Adyen Tab
     */
    createAdyenTab: function (enableTab) {
        var me = this;

        var transactionStore = Ext.create('Shopware.apps.AdyenPaymentNotificationsListingExtension.store.Notification');

        let items = [];
        if (enableTab) {
            items.push(
                    Ext.create('Shopware.apps.AdyenPaymentOrder.view.detail.TransactionDetails', {
                        store: transactionStore,
                        layout: {
                            type: 'vbox',
                            align: 'stretch'
                        },
                        region: 'north'
                    }),
                    Ext.create('Shopware.apps.AdyenPaymentOrder.view.detail.TransactionTabs', {
                        region: 'center',
                        store: transactionStore,
                        record: me.record
                    })
            );
        }

        me.adyenTransactionTab = Ext.create('Ext.container.Container', {
            title: 'Adyen Notifications',
            layout: 'border',
            items: items,
            disabled: !enableTab
        });

        me.adyenTransactionTab.addListener('activate', function () {
            transactionStore.load({
                params: {
                    filter: JSON.stringify([{
                        property: "orderId",
                        value: me.record.get('id'),
                        operator: null,
                        expression: '='
                    }])
                }
            });
        }, me);

        return me.adyenTransactionTab;
    }
});
//{/block}
