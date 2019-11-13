//{block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.MeteorAdyenOrder.view.detail.Window', {
    /**
     * Override the customer detail window
     * @string
     */
    override: 'Shopware.apps.Order.view.detail.Window',

    initComponent: function () {
        var me = this;
        console.log(me.record);
        me.callParent();
    },

    /**
     * Overwrite to add adyen transaction tab if necessary
     */
    createTabPanel: function () {
        var me = this,
            result = me.callParent();

        if (!me.record.raw.adyenTransaction) {
            return result;
        }

        result.add(me.createAdyenTab());

        return result;
    },

    /**
     * Generate Adyen Tab
     */
    createAdyenTab: function () {
        var me = this;

        var transactionStore = Ext.create('Shopware.apps.MeteorAdyenNotificationsListingExtension.store.Notification');

        me.transactionDetails = Ext.create('Shopware.apps.MeteorAdyenOrder.view.detail.TransactionDetails', {
            store: transactionStore,
            layout: {
                type: 'vbox',
                align: 'stretch'
            },
            region: 'north'
        });

        me.transactionTabsDetail = Ext.create('Shopware.apps.MeteorAdyenOrder.view.detail.TransactionTabs', {
            region: 'center',
            store: transactionStore,
            record: me.record
        });

        me.adyenTransactionTab = Ext.create('Ext.container.Container', {
            title: 'Adyen Transactions',
            layout: 'border',
            items: [
                me.transactionDetails,
                me.transactionTabsDetail
            ]
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