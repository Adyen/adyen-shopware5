
//{block name="backend/performance/view/main/multi_request_tasks" append}
Ext.define('Shopware.apps.Performance.view.main.RegisterApplePayMerchantAssociation', {
    override: 'Shopware.apps.Performance.view.main.MultiRequestTasks',

    initComponent: function() {
        this.addProgressBar(
            {
                initialText: 'Register ApplePay merchant association URLs',
                progressText: '[0] of [1] ApplePay merchant association URLs registered',
                requestUrl: '{url controller=registerapplepayassociationurl action=register}'
            },
            'registerapplepayassociationurl',
            'seo'
        );

        this.callParent(arguments);
    }
});
//{/block}
