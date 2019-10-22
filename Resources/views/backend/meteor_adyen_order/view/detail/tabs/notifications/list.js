//

Ext.define('Shopware.apps.MeteorAdyenOrder.view.detail.tabs.notifications.List', {
    extend: 'Ext.grid.Panel',

    initComponent: function() {
        var me = this;

        me.columns = me.getColumns();

        me.store.sort([{
            property : 'id',
            direction: 'DESC'
        }]);

        me.getSelectionModel().on('selectionchange', function(row, selected, options) {
            me.notifications.detailView.loadRecord(selected[0]);
            me.notifications.detailView.enable();
        });

        me.callParent(arguments);
    },

    getColumns: function() {
        return [{
            header: 'Date & time',
            dataIndex: 'createdAt',
            sortable: false,
            xtype:'datecolumn',
            format:'d.m.Y H:i:s',
        }, {
            header: 'Event code',
            dataIndex: 'eventCode',
            sortable: false,
        }, {
            header: 'Status',
            dataIndex: 'status',
            sortable: false,
        }];
    }
});