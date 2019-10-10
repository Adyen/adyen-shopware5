
Ext.define('Shopware.apps.MeteorAdyenNotificationsListingExtension', {
    extend: 'Enlight.app.SubApplication',

    name:'Shopware.apps.MeteorAdyenNotificationsListingExtension',

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

    launch: function() {
        return this.getController('Main').mainWindow;
    }
});