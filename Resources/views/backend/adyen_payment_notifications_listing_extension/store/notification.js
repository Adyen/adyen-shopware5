
Ext.define('Shopware.apps.AdyenPaymentNotificationsListingExtension.store.Notification', {
    extend:'Shopware.store.Listing',

    configure: function () {
        return {
            controller: 'AdyenPaymentNotificationsListingExtension'
        };
    },
    model: 'Shopware.apps.AdyenPaymentNotificationsListingExtension.model.Notification'
});