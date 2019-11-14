
Ext.define('Shopware.apps.MeteorAdyenNotificationsListingExtension.store.Notification', {
    extend:'Shopware.store.Listing',

    configure: function () {
        return {
            controller: 'MeteorAdyenNotificationsListingExtension'
        };
    },
    model: 'Shopware.apps.MeteorAdyenNotificationsListingExtension.model.Notification'
});