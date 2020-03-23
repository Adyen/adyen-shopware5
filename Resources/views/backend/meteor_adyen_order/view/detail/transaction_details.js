//

Ext.define('Shopware.apps.MeteorAdyenOrder.view.detail.TransactionDetails', {
    extend: 'Ext.container.Container',
    title: 'Transaction',
    record: null,

    initComponent: function () {
        var me = this;

        me.items  =  [
            me.createDetailsContainer()
        ];

        me.store.on('load', function (store, records, options ) {
            me.record = store.first();
            me.detailsPanel.loadRecord(me.record);
        });

        me.callParent(arguments);
    },

    /**
     * Creates the container for the detail form panel.
     * @return Ext.form.Panel
     */
    createDetailsContainer: function () {
        var me = this;

        me.detailsPanel = Ext.create('Ext.form.Panel', {
            title: 'Transaction details',
            titleAlign: 'left',
            bodyPadding: 10,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            margin: '10 0',
            items: [
                me.createInnerDetailContainer()
            ]
        });
        return me.detailsPanel;
    },

    /**
     * Creates the outer container for the detail panel which
     * has a column layout to display the detail information in two columns.
     *
     * @return Ext.container.Container
     */
    createInnerDetailContainer: function () {
        var me = this;

        return Ext.create('Ext.container.Container', {
            layout: 'column',
            items: [
                me.createDetailElementContainer(me.createLeftDetailElements()),
                me.createDetailElementContainer(me.createRightDetailElements())
            ]
        });
    },

    /**
     * Creates the column container for the detail elements which displayed
     * in two columns.
     *
     * @param { Array } items - The container items.
     */
    createDetailElementContainer: function (items) {
        return Ext.create('Ext.container.Container', {
            columnWidth: 0.5,
            defaults: {
                xtype: 'displayfield',
                labelWidth: 155
            },
            items: items
        });
    },

    /**
     * Creates the elements for the left column container which displays the
     * fields in two columns.
     *
     * @return array - Contains the form fields
     */
    createLeftDetailElements: function () {
        var me = this, fields;
        fields = [
            { name: 'pspReference', fieldLabel: 'PSP Reference'},
            { name: 'createdAt', fieldLabel: 'Created at'},
            { name: 'updatedAt', fieldLabel: 'Updated at'},
            { name: 'eventCode', fieldLabel: 'Event code'},
            { name: 'merchantAccountCode', fieldLabel: 'Merchant'},

        ];
        return fields;
    },

    /**
     * Creates the elements for the right column container which displays the
     * fields in two columns.
     *
     * @return Array - Contains the form fields
     */
    createRightDetailElements: function () {
        var me = this;

        return [
            { name: 'paymentMethod', fieldLabel: 'Payment method'},
            { name: 'amountValue', fieldLabel: 'Amount'},
            { name: 'amountCurrency', fieldLabel: 'Currency'},
            { name: 'status', fieldLabel: 'Status'},
            { name: 'success', fieldLabel: 'Success'},
            { name: 'errorDetails', fieldLabel: 'Error Details'},
        ];
    },

});