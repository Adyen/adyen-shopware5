//

Ext.define('Shopware.apps.MeteorAdyenOrder.view.tab.Transaction', {
    /**
     * @return Ext.container.Container
     */
    createTab: function(detailWindow) {
        var me = this;
        var transactionStore = Ext.create('Shopware.apps.MeteorAdyenNotificationsListingExtension.store.Notification');

        me.transactionDetail = Ext.create('Shopware.apps.MeteorAdyenOrder.view.detail.Transaction', {
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
            record: detailWindow.record
        });

        detailWindow.adyenTransactionTab = Ext.create('Ext.container.Container', {
            title: 'Adyen Transactions',
            layout: 'border',
            items: [
                me.transactionDetail,
                me.transactionTabsDetail
            ]
        });

        detailWindow.adyenTransactionTab.addListener('activate', function() {
            transactionStore.load({
                params: {
                    filter: JSON.stringify([{
                        property: "orderId",
                        value: detailWindow.record.get('id'),
                        operator: null,
                        expression: '='
                    }])
                }
            });
        }, me);

        return detailWindow.adyenTransactionTab;
    }
});