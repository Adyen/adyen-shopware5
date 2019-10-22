//

Ext.define('Shopware.apps.MeteorAdyenOrder.view.detail.TransactionTabs', {
    extend: 'Ext.tab.Panel',

    border: 0,
    bodyBorder: false,
    tabBarPosition: 'bottom',

    initComponent: function() {
        var me = this;
        me.callParent(arguments);

        me.createItems();
    },

    defaults: {
        styleHtmlContent: true
    },

    createItems: function() {
        var me = this;
        me.add(me.createNotificationsTab());
        me.add(me.createRefundsTab());

        me.doLayout();
        me.setActiveTab(0);
    },

    createNotificationsTab: function() {
        var me = this;
        me.tabNotifications = Ext.create('Shopware.apps.MeteorAdyenOrder.view.detail.tabs.Notifications', {
            title: 'Notifications',
            record: me.record,
            store: me.store,
        });
        return me.tabNotifications;
    },

    createRefundsTab: function() {
        var me = this;
        me.tabRefunds = Ext.create('Shopware.apps.MeteorAdyenOrder.view.detail.tabs.Refunds', {
            title: 'Refunds',
            record: me.record,
            store: me.store,
        });
        return me.tabRefunds;
    },
});