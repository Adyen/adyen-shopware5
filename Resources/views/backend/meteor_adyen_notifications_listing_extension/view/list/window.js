
Ext.define('Shopware.apps.MeteorAdyenNotificationsListingExtension.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.product-list-window',
    height: 450,
    title : '{s name=window_title}Notification listing{/s}',

    configure: function() {
        return {
            listingGrid: 'Shopware.apps.MeteorAdyenNotificationsListingExtension.view.list.Notification',
            listingStore: 'Shopware.apps.MeteorAdyenNotificationsListingExtension.store.Notification',
        };
    }
});