
// {namespace name=backend/adyen/notification/listing}
Ext.define('Shopware.apps.AdyenPaymentNotificationsListingExtension.controller.Main', {
    extend: 'Enlight.app.Controller',

    init: function () {
        var me = this;

        me.mainWindow = me.getView('list.Window').create({ }).show();
    },
});