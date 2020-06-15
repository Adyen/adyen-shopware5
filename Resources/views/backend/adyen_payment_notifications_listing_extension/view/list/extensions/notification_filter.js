Ext.define('Shopware.apps.AdyenPaymentNotificationsListingExtension.view.list.extensions.NotificationFilter', {
    extend: 'Shopware.listing.FilterPanel',
    alias:  'widget.notification-listing-filter-panel',
    width: 270,
    controller: 'AdyenPaymentNotificationsListingExtension',

    configure: function () {
        var me = this;

        return {
            controller: me.controller,
            model: 'Shopware.apps.AdyenPaymentNotificationsListingExtension.model.Notification',
            fields: {
                createdAt: { },
                updatedAt: { },
                status: {
                    xtype: 'combobox',
                    displayField: 'status',
                    valueField: 'status',
                    store: new Ext.data.Store({
                        autoLoad: true,
                        proxy: {
                            type: 'ajax',
                            url: window.location.href.substr(0, window.location.href.indexOf('backend')) + 'backend/' + me.controller + '/getNotificationStatusses',
                            reader: {
                                type: 'json',
                                root: 'statusses'
                            }
                        },
                        fields: [
                            'status'
                        ]
                    }),
                fieldLabel: '{s name="column/status"}Status{/s}',
                },
                eventCode: {
                    xtype: 'combobox',
                    displayField: 'eventCode',
                    valueField: 'eventCode',
                    store: new Ext.data.Store({
                        autoLoad: true,
                        proxy: {
                            type: 'ajax',
                            url: window.location.href.substr(0, window.location.href.indexOf('backend')) + 'backend/' + me.controller + '/getEventCodes',
                            reader: {
                                type: 'json',
                                root: 'eventCodes'
                            }
                        },
                        fields: [
                            'eventCode'
                        ]
                    }),
                fieldLabel: '{s name="column/eventCode"}Event Code{/s}',
                },
            }
        };
    },
});