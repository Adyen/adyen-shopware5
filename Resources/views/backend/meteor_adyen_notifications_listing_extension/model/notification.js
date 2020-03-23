
Ext.define('Shopware.apps.MeteorAdyenNotificationsListingExtension.model.Notification', {
    extend: 'Shopware.data.Model',

    configure: function () {
        return {
            controller: 'MeteorAdyenNotificationsListingExtension',
        };
    },

    fields: [
        { name : 'id', type: 'int', useNull: true },
        { name : 'pspReference', type: 'string' },
        { name : 'createdAt', type: 'date' },
        { name : 'updatedAt', type: 'date' },
        { name : 'status', type: 'string' },
        { name : 'paymentMethod', type: 'string' },
        { name : 'eventCode', type: 'string' },
        { name : 'success', type: 'string' },
        { name : 'merchantAccountCode', type: 'string' },
        { name : 'amountValue', type: 'float', convert: function(v) {
                return v / 100;
        } },
        { name : 'amountCurrency', type: 'string' },
        { name : 'errorDetails', type: 'string' },
        { name : 'orderId', type: 'int' },
    ],

    associations: [
        {
            relation: 'ManyToOne',
            field: 'orderId',
            type: 'hasMany',
            model: 'Shopware.apps.Order.model.Order',
            name: 'getOrder',
            associationKey: 'id'
    },
    ]

});

