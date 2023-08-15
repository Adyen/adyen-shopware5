//{block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.AdyenTransaction.Window', {
    override: 'Shopware.apps.Order.view.detail.Window',

    initComponent: function () {
        var me = this;
        me.callParent();
    },

    createTabPanel: function () {
        let me = this,
            result = me.callParent();

        result.add(me.createAdyenTab(!!me.record.raw.adyenTransaction));

        return result;
    },

    createAdyenTab: function (enableTab) {
        var me = this;

        var transactionStore = Ext.create('Shopware.apps.AdyenTransaction.store.Transaction');

        let items = [];
        if (enableTab) {
            items.push(
                Ext.create('Shopware.apps.AdyenTransaction.AdyenOrderDetailData', {
                    store: transactionStore,
                    layout: {
                        type: 'vbox',
                        align: 'stretch'
                    },
                    region: 'north'
                }),
                Ext.create('Shopware.apps.AdyenTransaction.AdyenOrderDetailList', {
                    region: 'center',
                    store: transactionStore,
                    record: me.record
                })
            );
        }

        me.adyenTransactionTab = Ext.create('Ext.container.Container', {
            title: 'Adyen',
            itemId: 'adyen-tab',
            layout: 'border',
            items: items,
            disabled: !enableTab
        });

        me.loadingMask = new Ext.LoadMask(me.adyenTransactionTab);
        me.adyenTransactionTab.addListener('activate', function () {
            me.loadingMask.show();
            Ext.Ajax.request({
                method: 'GET',
                url: '{url controller=AdyenTransaction action="get"}',
                params: {
                    temporaryId: me.record.get('temporaryId'),
                    storeId: me.record.get('shopId')
                },
                success: function (response) {
                    transactionStore.loadData(JSON.parse(response.responseText));
                    me.loadingMask.hide();
                }
            });
        }, me);

        return me.adyenTransactionTab;
    }
});
//{/block}
