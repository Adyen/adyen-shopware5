//{namespace name=backend/adyen/configuration}

Ext.define('Shopware.apps.AdyenTransaction.AdyenOrderDetailData', {
    extend: 'Ext.container.Container',
    record: null,
    cls: 'shopware-form',

    initComponent: function () {
        var me = this;

        me.items = [
            me.createDetailsContainer()
        ];

        me.store.on('datachanged', function (store, records, options) {
            me.record = store.last();
            me.detailsPanel.loadRecord(me.record);
            me.query('#adyenPaymentMethod')[0].setSrc(me.record.get('paymentMethod'));
            me.query('#captureCurrency')[0].setValue(me.record.get('amountCurrency'));
            me.query('#refundCurrency')[0].setValue(me.record.get('amountCurrency'));

            if (!me.record.get('captureSupported') || (parseFloat(me.record.get('capturableAmount')) === 0)) {
                me.query('#adyenCaptureToolbar')[0].hide();
            } else {
                me.query('#adyenCaptureToolbar')[0].show();
            }

            if (!me.record.get('partialCapture')) {
                me.query('#adyenCaptureAmount')[0].hide();
                me.query('#captureCurrency')[0].hide();
            } else {
                me.query('#adyenCaptureAmount')[0].show();
                me.query('#captureCurrency')[0].show();
            }

            me.query('#adyenCaptureAmount')[0].maxValue = parseFloat(me.record.get('capturableAmount'));
            if (!me.record.get('refund') || (parseFloat(me.record.get('refundableAmount')) === 0)) {
                me.query('#adyenRefundToolbar')[0].hide();
            } else {
                me.query('#adyenRefundToolbar')[0].show();
            }

            if (!me.record.get('partialRefund')) {
                me.query('#adyenRefundAmount')[0].hide();
                me.query('#refundCurrency')[0].hide();
            } else {
                me.query('#adyenRefundAmount')[0].show();
                me.query('#refundCurrency')[0].show();
            }

            me.query('#adyenRefundAmount')[0].maxValue = parseFloat(me.record.get('refundableAmount'));

            if (!me.record.get('cancelSupported')) {
                me.query('#adyenCancelBtn')[0].hide();
            } else {
                me.query('#adyenCancelBtn')[0].show();
            }

            if (!me.record.get('displayPaymentLink')) {
                me.query('#adyenPaymentLinkToolbar')[0].hide();
            }

            if (me.record.get('displayPaymentLink') && !me.record.get('paymentLink')) {
                me.query('#adyenPaymentLinkField')[0].hide();
                me.query('#adyenCopyPaymentLinkBtn')[0].hide();
                me.query('#adyenPaymentLinkToolbar')[0].show();
                me.query('#adyenGeneratePaymentLinkBtn')[0].show();
            }

            if (me.record.get('displayPaymentLink') && me.record.get('paymentLink')) {
                me.query('#adyenGeneratePaymentLinkBtn')[0].hide();
                me.query('#adyenPaymentLinkField')[0].setValue(me.record.get('paymentLink'));
                me.query('#adyenPaymentLinkToolbar')[0].show();
                me.query('#adyenPaymentLinkField')[0].show();
                me.query('#adyenCopyPaymentLinkBtn')[0].show();
            }
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
            title: '{s name="payment/adyen/detail/transaction"}Transaction{/s}',
            titleAlign: 'left',
            bodyPadding: 10,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            margin: '10 0',
            width: '100%',
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
            { name: 'pspReference', fieldLabel: '{s name="payment/adyen/detail/pspreference"}PSP Reference{/s}'},
            { name: 'date', fieldLabel: '{s name="payment/adyen/detail/date"}Date{/s}'},
            { name: 'eventCode', fieldLabel: '{s name="payment/adyen/detail/eventcode"}Event code{/s}'},
            { name: 'merchantAccountCode', fieldLabel: '{s name="payment/adyen/detail/merchant"}Merchant{/s}'},
            {
                xtype: 'toolbar',
                itemId: 'adyenCaptureToolbar',
                style: {
                    'background-color': 'rgb(240, 242, 244)'
                },
                items: [
                    {
                        xtype: 'numberfield',
                        hideTrigger: true,
                        minValue: 0,
                        name: 'capturableAmount',
                        itemId: 'adyenCaptureAmount',
                        fieldLabel: '',
                        allowBlank: false,
                        width: 250,
                        forcePrecision: true,
                        decimalPrecision: 3
                    },
                    {
                        xtype: 'displayfield',
                        itemId: 'captureCurrency',
                        name: 'captureCurrency',
                        margin: '0 10 0 0'
                    },
                    {
                        action: 'capturePayment',
                        xtype: 'button',
                        itemId: 'adyenCaptureBtn',
                        cls: 'primary',
                        text: 'Capture',
                        margin: '10 0 0 0',
                        width: 105
                    }
                ]
            },
            {
                xtype: 'toolbar',
                itemId: 'adyenRefundToolbar',
                style: {
                    'background-color': 'rgb(240, 242, 244)'
                },
                items: [
                    {
                        xtype: 'numberfield',
                        hideTrigger: true,
                        minValue: 0,
                        name: 'refundableAmount',
                        itemId: 'adyenRefundAmount',
                        fieldLabel: '',
                        allowBlank: false,
                        width: 250,
                        forcePrecision: true,
                        decimalPrecision: 3
                    },
                    {
                        xtype: 'displayfield',
                        itemId: 'refundCurrency',
                        name: 'refundCurrency',
                        margin: '0 10 0 0'
                    },
                    {
                        action: 'refundPayment',
                        xtype: 'button',
                        itemId: 'adyenRefundBtn',
                        cls: 'primary',
                        text: '{s name="payment/adyen/detail/refund"}Refund{/s}',
                        margin: '10 0 0 0',
                        width: 105
                    }
                ]
            },
            {
                xtype: 'toolbar',
                itemId: 'adyenCancelToolBar',
                style: {
                    'background-color': 'rgb(240, 242, 244)'
                },
                items: [
                    {
                        action: 'cancelPayment',
                        xtype: 'button',
                        itemId: 'adyenCancelBtn',
                        cls: 'primary',
                        text: '{s name="payment/adyen/detail/cancel"}Cancel{/s}',
                        margin: '10 10 0 0',
                        width: 105
                    },
                    {
                        action: 'viewOnAdyen',
                        xtype: 'button',
                        cls: 'secondary',
                        text: '{s name="payment/adyen/detail/viewonadyenca"}View payment on Adyen CA{/s}',
                        margin: '10 0 0 0',
                        handler: function () {
                            window.open(me.record.get('viewOnAdyenUrl'));
                        }
                    }
                ]
            },
            {
                xtype: 'toolbar',
                itemId: 'adyenPaymentLinkToolbar',
                style: {
                    'background-color': 'rgb(240, 242, 244)'
                },
                items: [
                    {
                        xtype: 'textfield',
                        itemId: 'adyenPaymentLinkField',
                        width: 250,
                        disabled: true
                    },
                    {
                        action: 'copyPaymentLink',
                        xtype: 'button',
                        itemId: 'adyenCopyPaymentLinkBtn',
                        cls: 'primary',
                        text: '{s name="payment/adyen/detail/copy"}Copy payment link{/s}'
                    },
                    {
                        action: 'generatePaymentLink',
                        xtype: 'button',
                        itemId: 'adyenGeneratePaymentLinkBtn',
                        cls: 'primary',
                        text: '{s name="payment/adyen/detail/generatePaymentLink"}Generate a payment link{/s}',
                        margin: '10 0 0 0'
                    },
                ]
            }
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
            {
                xtype: 'panel',
                layout: 'hbox',
                border: false,
                height: 30,
                items: [
                    {
                        xtype: 'displayfield',
                        labelWidth: 155,
                        fieldLabel: '{s name="payment/adyen/detail/paymentmethod"}Payment method{/s}',
                        height: 30
                    },
                    {
                        xtype: 'image',
                        itemId: 'adyenPaymentMethod',
                        name: 'paymentMethod',
                        src: '',
                        height: 30
                    },
                ]
            },
            { name: 'amountCurrency', fieldLabel: '{s name="payment/adyen/detail/currency"}Currency{/s}'},
            { name: 'status', fieldLabel: '{s name="payment/adyen/detail/status"}Status{/s}'},
            { name: 'success', fieldLabel: '{s name="payment/adyen/detail/success"}Success{/s}'},
            { name: 'riskScore', fieldLabel: '{s name="payment/adyen/detail/riskscore"}Risk score{/s}'},
            { name: 'paidAmount', fieldLabel: '{s name="payment/adyen/detail/paidamount"}Paid amount{/s}'},
            { name: 'refundedAmount', fieldLabel: '{s name="payment/adyen/detail/refundedamount"}Refunded amount{/s}'},
            {
                xtype: 'hidden',
                itemId: 'adyenMerchantReference',
                name: 'merchantReference'
            },
            {
                xtype: 'hidden',
                itemId: 'adyenStoreId',
                name: 'storeId',
            },
            {
                xtype: 'hidden',
                itemId: 'adyenCurrencyIso',
                name: 'currencyIso'
            }
        ];
    },
});

Ext.define('Shopware.apps.AdyenTransaction.AdyenOrderPaymentLink', {
    extend: 'Ext.container.Container',
    record: null,
    alias: 'widget.adyen-order-payment-link',
    cls: 'shopware-form',

    initComponent: function () {
        var me = this;

        me.items = [
            me.createDetailsContainer()
        ];

        me.store.on('datachanged', function (store, records, options) {
            me.record = store.first();
            me.detailsPanel.loadRecord(me.record);
            me.query('#adyenPaymentLinkToolbar')[0].show();
            if (me.record !== undefined) {
                let paymentLink = me.record.get('paymentLink');
                me.query('#adyenPaymentLinkNonAdyenOrderField')[0].setValue(paymentLink);
                me.query('#adyenGeneratePaymentLinkNonAdyenOrderBtn')[0].hide();
                me.query('#adyenPaymentLinkNonAdyenOrderField')[0].show();
                me.query('#adyenCopyPaymentLinkNonAdyenOrderBtn')[0].show();
            } else {
                me.query('#adyenGeneratePaymentLinkNonAdyenOrderBtn')[0].show();
                me.query('#adyenPaymentLinkNonAdyenOrderField')[0].hide();
                me.query('#adyenCopyPaymentLinkNonAdyenOrderBtn')[0].hide();
            }
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
            title: '{s name="payment/adyen/detail/paymentLink"}Payment Link{/s}',
            titleAlign: 'left',
            bodyPadding: 10,
            layout: 'anchor',
            defaults: {
                anchor: '100%'
            },
            margin: '10 0',
            width: '100%',
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
            {
                xtype: 'toolbar',
                itemId: 'adyenPaymentLinkToolbar',
                style: {
                    'background-color': 'rgb(240, 242, 244)'
                },
                items: [
                    {
                        action: 'generatePaymentLinkNonAdyenOrder',
                        xtype: 'button',
                        itemId: 'adyenGeneratePaymentLinkNonAdyenOrderBtn',
                        cls: 'primary',
                        text: '{s name="payment/adyen/detail/generatePaymentLink"}Generate a payment link{/s}',
                        margin: '10 0 0 0'
                    },
                    {
                        xtype: 'hiddenfield',
                        name: 'adyenOrderId',
                        itemId: 'adyenOrderId'
                    },
                    {
                        xtype: 'textfield',
                        itemId: 'adyenPaymentLinkNonAdyenOrderField',
                        width: 250,
                        disabled: true
                    },
                    {
                        xtype: 'button',
                        itemId: 'adyenCopyPaymentLinkNonAdyenOrderBtn',
                        cls: 'primary',
                        text: '{s name="payment/adyen/detail/copy"}Copy payment link{/s}'
                    }
                ]
            }
        ];

        return fields;
    },
});
