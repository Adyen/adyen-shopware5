
//{block name="backend/performance/view/main/multi_request_tasks" append}
Ext.define('Shopware.apps.Performance.view.main.ApplePayCertificate', {
    override: 'Shopware.apps.Performance.view.main.MultiRequestTasks',

    initComponent: function() {
        this.addProgressBar(
            {
                initialText: 'Apple Pay Certificate URLs',
                progressText: '[0] of [1] Apple Pay Certificate URLs',
                requestUrl: '{url controller=applepaycertificate action=generateSeoUrl}'
            },
            'applepaycertificate',
            'seo'
        );

        this.callParent(arguments);
    }
});
//{/block}
