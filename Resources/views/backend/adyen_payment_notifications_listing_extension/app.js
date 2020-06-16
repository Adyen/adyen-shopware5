
Ext.define('Shopware.apps.AdyenPaymentNotificationsListingExtension', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.AdyenPaymentNotificationsListingExtension',

    loadPath: '{url action=load}',
    bulkLoad: true,

    controllers: [ 'Main' ],

    views: [
        'list.Window',
        'list.Notification',
        'list.extensions.NotificationFilter',
    ],

    models: [
        'Notification',
    ],

    stores: [
        'Notification',
    ],

    launch: function () {
        return this.getController('Main').mainWindow;
    }
});