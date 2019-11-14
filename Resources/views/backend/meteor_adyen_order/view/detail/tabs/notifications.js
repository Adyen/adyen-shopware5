//

Ext.define('Shopware.apps.MeteorAdyenOrder.view.detail.tabs.Notifications', {
    extend: 'Ext.container.Container',

    layout: 'border',
    formActions: {},

    initComponent: function () {
        var me = this;
        me.items = me.getNotifications();
        me.callParent(arguments);
    },

    getNotifications: function () {
        var me = this;

        return [
            Ext.create('Ext.container.Container', {
                layout: 'border',
                region: 'center',
                items: [
                    me.getWidgetList(),
                    me.getWidgetDetail()
                ]
            })
        ];
    },

    getWidgetList: function () {
        var me = this;

        me.listView = Ext.create('Shopware.apps.MeteorAdyenOrder.view.detail.tabs.notifications.List', {
            store: me.store,
            notifications: me,
            flex: 1,
            region: 'west'
        });
        return me.listView;
    },

    getWidgetDetail: function () {
        var me = this;

        me.detailView = Ext.create('Shopware.apps.MeteorAdyenOrder.view.detail.tabs.notifications.Detail', {
            flex: 2,
            region: 'center'
        });

        me.detailView.disable();
        return me.detailView;
    },
});