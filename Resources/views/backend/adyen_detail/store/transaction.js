//{block name="backend/adyen/transaction"}
Ext.define('Shopware.apps.AdyenTransaction.store.Transaction', {
    extend:'Shopware.store.Listing',
    model: 'Shopware.apps.AdyenTransaction.model.Transaction',
    autoLoad: false,
    proxy: {
        type: 'ajax',
        url: '{url controller=AdyenTransaction action="get"}',
        reader: {
            type: 'json',
            root: 'data'
        }
    },
    configure: function () {
        return {
            controller: 'AdyenTransaction',
            action: 'get'
        };
    }
});
//{/block}
