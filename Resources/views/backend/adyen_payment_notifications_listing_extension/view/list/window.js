
Ext.define('Shopware.apps.AdyenPaymentNotificationsListingExtension.view.list.Window', {
    extend: 'Shopware.window.Listing',
    alias: 'widget.product-list-window',
    height: 450,
    title : '{s name="window_title"}Notification listing{/s}',

    configure: function () {
        return {
            listingGrid: 'Shopware.apps.AdyenPaymentNotificationsListingExtension.view.list.Notification',
            listingStore: 'Shopware.apps.AdyenPaymentNotificationsListingExtension.store.Notification',

            extensions: [
                { xtype: 'notification-listing-filter-panel' }
            ]
        };
    }
});