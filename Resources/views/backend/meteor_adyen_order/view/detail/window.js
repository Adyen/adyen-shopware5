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

        var payment = me.record.getPayment().first();
        if(payment.raw.name !== 'adyen_general_payment_method') {
            return result;
        }

        result.add(Ext.create('Shopware.apps.MeteorAdyenOrder.view.detail.Transaction', {
            title: 'Adyen Transaction',
            record: me.record
        }));

        return result;
    }
});
//{/block}