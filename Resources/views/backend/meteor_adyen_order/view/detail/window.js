//{block name="backend/order/view/detail/window"}
// {$smarty.block.parent}
Ext.define('Shopware.apps.MeteorAdyenOrder.view.detail.Window', {
    /**
     * Override the customer detail window
     * @string
     */
    override: 'Shopware.apps.Order.view.detail.Window',

    initComponent: function() {
        var me = this;
        me.callParent();
    },

    createTabPanel: function() {
        var me = this,
            result = me.callParent();

        if(!me.record.raw.adyenTransaction) {
            return result;
        }

        me.adyenTransactionTab = Ext.create('Shopware.apps.MeteorAdyenOrder.view.tab.Transaction');
        result.add(me.adyenTransactionTab.createTab(me));

        return result;
    }
});
//{/block}