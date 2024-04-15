// Adyen order details controller
//{block name="backend/order/controller/detail" append}

Ext.define('Shopware.apps.AdyenTransaction.controller.OrderDetailsController', {
    /**
     * Override the order details main controller
     * @string
     */
    override: 'Shopware.apps.Order.controller.Detail',


    onTabChange: function () {
        let me = this,
            captureBtn = Ext.WindowManager.getActive().down('#adyenCaptureBtn'),
            cancelBtn = Ext.WindowManager.getActive().down('#adyenCancelBtn'),
            refundBtn = Ext.WindowManager.getActive().down('#adyenRefundBtn'),
            generateLinkBtn = Ext.WindowManager.getActive().down('#adyenGeneratePaymentLinkBtn'),
            copyLinkBtn = Ext.WindowManager.getActive().down('#adyenCopyPaymentLinkBtn'),
            generateLinkNonAdyenOrderBtn = Ext.WindowManager.getActive().down('#adyenGeneratePaymentLinkNonAdyenOrderBtn'),
            copyLinkNonAdyenOrderBtn = Ext.WindowManager.getActive().down('#adyenCopyPaymentLinkNonAdyenOrderBtn'),
            extendAuthorizationPeriodBtm = Ext.WindowManager.getActive().down('#adyenExtendAuthorizationBtn');

        // me.callParent will execute the init function of the overridden controller
        me.callParent(arguments);

        if (cancelBtn !== null && !cancelBtn.adyenClickAttached) {
            cancelBtn.on('click', me.cancel.bind(me));
            cancelBtn.adyenClickAttached = true;
        }

        if (captureBtn !== null && !captureBtn.adyenClickAttached) {
            captureBtn.on('click', me.capture.bind(me));
            captureBtn.adyenClickAttached = true;
        }

        if (refundBtn !== null && !refundBtn.adyenClickAttached) {
            refundBtn.on('click', me.refund.bind(me));
            refundBtn.adyenClickAttached = true;
        }

        if (generateLinkBtn !== null && !generateLinkBtn.adyenClickAttached) {
            generateLinkBtn.on('click', me.generatePaymentLink.bind(me));
            generateLinkBtn.adyenClickAttached = true;
        }

        if (copyLinkBtn !== null && !copyLinkBtn.adyenClickAttached) {
            copyLinkBtn.on('click', me.copyPaymentLink.bind(me));
            copyLinkBtn.adyenClickAttached = true;
        }

        if (generateLinkNonAdyenOrderBtn !== null && !generateLinkNonAdyenOrderBtn.adyenClickAttached) {
            generateLinkNonAdyenOrderBtn.on('click', me.generateLinkNonAdyenOrder.bind(me));
            generateLinkNonAdyenOrderBtn.adyenClickAttached = true;
        }

        if (copyLinkNonAdyenOrderBtn !== null && !copyLinkNonAdyenOrderBtn.adyenClickAttached) {
            copyLinkNonAdyenOrderBtn.on('click', me.copyLinkNonAdyenOrder.bind(me));
            copyLinkNonAdyenOrderBtn.adyenClickAttached = true;
        }

        if (extendAuthorizationPeriodBtm !== null && !extendAuthorizationPeriodBtm.adyenClickAttached) {
            extendAuthorizationPeriodBtm.on('click', me.extendAuthorization.bind(me));
            extendAuthorizationPeriodBtm.adyenClickAttached = true;
        }
    },

    capture: function () {
        let amount = Ext.WindowManager.getActive().down('#adyenCaptureAmount'),
            currencyIso = Ext.WindowManager.getActive().down('#adyenCurrencyIso'),
            merchantReference = Ext.WindowManager.getActive().down('#adyenMerchantReference'),
            storeId = Ext.WindowManager.getActive().down('#adyenStoreId'),
            tab = Ext.WindowManager.getActive().down('#adyen-tab'),
            me = this;

        me.loadingMask = new Ext.LoadMask(tab);
        me.loadingMask.show();

        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=AdyenMerchantActions action="capture"}',
            params: {
                amount: amount.getValue(),
                currency: currencyIso.getValue(),
                merchantReference: merchantReference.getValue(),
                storeId: storeId.getValue()
            },
            success: function () {
                me.refreshTable(merchantReference, storeId);
            },
            failure: function (response, options) {
                me.refreshTable(merchantReference, storeId);
                Shopware.Notification.createStickyGrowlMessage({
                    title: Ext.String.format(
                        "{s name='notification/adyen/header'}You have new Adyen notifications {/s}",
                    ),
                    text: "{s name='notification/adyen/message'}" + response.responseText + "{/s}",
                })
            }
        });
    },

    cancel: function () {
        let me = this,
            merchantReference = Ext.WindowManager.getActive().down('#adyenMerchantReference'),
            storeId = Ext.WindowManager.getActive().down('#adyenStoreId'),
            tab = Ext.WindowManager.getActive().down('#adyen-tab');

        me.loadingMask = new Ext.LoadMask(tab);
        me.loadingMask.show();

        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=AdyenMerchantActions action="cancel"}',
            params: {
                merchantReference: merchantReference.getValue(),
                storeId: storeId.getValue()
            },
            success: function () {
                me.refreshTable(merchantReference, storeId);
            },
            failure: function (response, options) {
                me.refreshTable(merchantReference, storeId);
                Shopware.Notification.createStickyGrowlMessage({
                    title: Ext.String.format(
                        "{s name='notification/adyen/header'}You have new Adyen notifications {/s}",
                    ),
                    text: "{s name='notification/adyen/message'}" + response.responseText + "{/s}",
                })
            }
        });
    },

    refund: function () {
        let me = this,
            amount = Ext.WindowManager.getActive().down('#adyenRefundAmount'),
            currencyIso = Ext.WindowManager.getActive().down('#adyenCurrencyIso'),
            merchantReference = Ext.WindowManager.getActive().down('#adyenMerchantReference'),
            storeId = Ext.WindowManager.getActive().down('#adyenStoreId'),
            tab = Ext.WindowManager.getActive().down('#adyen-tab');

        me.loadingMask = new Ext.LoadMask(tab);
        me.loadingMask.show();

        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=AdyenMerchantActions action="refund"}',
            params: {
                amount: amount.getValue(),
                currency: currencyIso.getValue(),
                merchantReference: merchantReference.getValue(),
                storeId: storeId.getValue()
            },
            success: function (response) {
                me.refreshTable(merchantReference, storeId);
            },
            failure: function (response, options) {
                // Handle failed response
                me.refreshTable(merchantReference, storeId);
                Shopware.Notification.createStickyGrowlMessage({
                    title: Ext.String.format(
                        "{s name='notification/adyen/header'}You have new Adyen notifications {/s}",
                    ),
                    text: "{s name='notification/adyen/message'}" + response.responseText + "{/s}",
                })
            }
        });
    },

    refreshTable: function (merchantReference, storeId) {
        let me = this;

        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=AdyenTransaction action="get"}',
            params: {
                temporaryId: merchantReference.getValue(),
                storeId: storeId.getValue()
            },
            success: function (response) {
                var list = Ext.WindowManager.getActive().down('adyen-order-detail-list');
                list.getStore().loadData(JSON.parse(response.responseText));
                list.getView().refresh()
                me.loadingMask.hide();
            }
        });
    },

    generatePaymentLink: function () {
        let me = this,
            amount = Ext.WindowManager.getActive().down('#adyenCaptureAmount'),
            currencyIso = Ext.WindowManager.getActive().down('#adyenCurrencyIso'),
            merchantReference = Ext.WindowManager.getActive().down('#adyenMerchantReference'),
            storeId = Ext.WindowManager.getActive().down('#adyenStoreId'),
            tab = Ext.WindowManager.getActive().down('#adyen-tab');

        me.loadingMask = new Ext.LoadMask(tab);
        me.loadingMask.show();

        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=AdyenMerchantActions action="generatePaymentLink"}',
            params: {
                amount: amount.getValue(),
                currency: currencyIso.getValue(),
                merchantReference: merchantReference.getValue(),
                storeId: storeId.getValue()
            },
            success: function (response) {
                me.refreshTable(merchantReference, storeId);
            },
            failure: function (response, options) {
                // Handle failed response
                me.refreshTable(merchantReference, storeId);
                Shopware.Notification.createStickyGrowlMessage({
                    title: Ext.String.format(
                        "{s name='notification/adyen/header'}You have new Adyen notifications {/s}",
                    ),
                    text: "{s name='notification/adyen/message'}" + response.responseText + "{/s}",
                })
            }
        });
    },

    copyPaymentLink: function () {
        navigator.clipboard.writeText(Ext.WindowManager.getActive().down('#adyenPaymentLinkField').getValue())
    },

    generateLinkNonAdyenOrder: function () {
        let me = this,
            orderId = Ext.WindowManager.getActive().down('#adyenOrderId'),
            tab = Ext.WindowManager.getActive().down('#adyen-tab');

        me.loadingMask = new Ext.LoadMask(tab);
        me.loadingMask.show();

        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=AdyenMerchantActions action="generatePaymentLinkNonAdyenOrder"}',
            params: {
                orderId: orderId.getValue()
            },
            success: function (response) {
                let responseObject = Ext.decode(response.responseText);
                Ext.WindowManager.getActive().down('#adyenGeneratePaymentLinkNonAdyenOrderBtn').hide();
                Ext.WindowManager.getActive().down('#adyenPaymentLinkNonAdyenOrderField').show();
                Ext.WindowManager.getActive().down('#adyenPaymentLinkNonAdyenOrderField').setValue(responseObject.paymentLink);
                Ext.WindowManager.getActive().down('#adyenCopyPaymentLinkNonAdyenOrderBtn').show();
                Ext.WindowManager.getActive().record.set('temporaryId', responseObject.temporaryId);
                Ext.WindowManager.getActive().record.set('changed', responseObject.changed);
                me.loadingMask.hide();
            },
            failure: function (response, options) {
                // Handle failed response
                me.loadingMask.hide();
                Shopware.Notification.createStickyGrowlMessage({
                    title: Ext.String.format(
                        "{s name='notification/adyen/header'}You have new Adyen notifications {/s}",
                    ),
                    text: "{s name='notification/adyen/message'}" + response.responseText + "{/s}",
                })
            }
        });
    },

    copyLinkNonAdyenOrder: function () {
        navigator.clipboard.writeText(Ext.WindowManager.getActive().down('#adyenPaymentLinkNonAdyenOrderField').getValue())
    },

    extendAuthorization: function () {
        let merchantReference = Ext.WindowManager.getActive().down('#adyenMerchantReference'),
            storeId = Ext.WindowManager.getActive().down('#adyenStoreId'),
            tab = Ext.WindowManager.getActive().down('#adyen-tab'),
            me = this;

        me.loadingMask = new Ext.LoadMask(tab);
        me.loadingMask.show();

        Ext.Ajax.request({
            method: 'GET',
            url: '{url controller=AdyenMerchantActions action="extendAuthorization"}',
            params: {
                merchantReference: merchantReference.getValue(),
                storeId: storeId.getValue()
            },
            success: function () {
                me.refreshTable(merchantReference, storeId);
            },
            failure: function (response, options) {
                me.refreshTable(merchantReference, storeId);
                Shopware.Notification.createStickyGrowlMessage({
                    title: Ext.String.format(
                        "{s name='notification/adyen/header'}You have new Adyen notifications {/s}",
                    ),
                    text: "{s name='notification/adyen/message'}" + response.responseText + "{/s}",
                })
            }
        });
    }
});

//{/block}
