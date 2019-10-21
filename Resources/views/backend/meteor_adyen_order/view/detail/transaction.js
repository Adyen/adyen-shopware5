//

Ext.define('Shopware.apps.MeteorAdyenOrder.view.detail.Transaction', {
    extend: 'Ext.container.Container',
    padding: 10,
    title: 'Transaction',

    initComponent: function() {
        var me = this;

        var transactionStore = Ext.data.StoreManager.get("Shopware.apps.MeteorAdyenNotificationsListingExtension.store.Notification");
        if (!transactionStore) {
            transactionStore = Ext.create("Shopware.apps.MeteorAdyenNotificationsListingExtension.store.Notification");
        }
        this.transactionStore = transactionStore;
        var transaction = this.transactionStore.load({
            params: {
                filter: JSON.stringify([
                    {
                        property: "orderId",
                        value: 91,
                        operator: null,
                        expression: "="
                    }
                ])
            },
            callback: function(records, operation, success) {
                console.log(records);
                var transaction = records[0];
                console.log(transaction.raw);

                me.items  =  [
                    me.createDetailsContainer()
                ];

                me.detailsForm.loadRecord(transaction.raw);
            },
            scope: this
        });

        me.callParent(arguments);
    },

    /**
     * Creates the container for the detail form panel.
     * @return Ext.form.Panel
     */
    createDetailsContainer: function () {
        var me = this;

        me.detailsForm = Ext.create('Ext.form.Panel', {
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
        return me.detailsForm;
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
            { name: 'shop[name]', fieldLabel: 'shop'},
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
            { name: 'referer', fieldLabel: 'referer'},
        ];
    },

});