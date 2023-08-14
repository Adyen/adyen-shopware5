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
            refundBtn = Ext.WindowManager.getActive().down('#adyenRefundBtn');

        // me.callParent will execute the init function of the overridden controller
        me.callParent(arguments);

        if (cancelBtn !== null && !cancelBtn.hasListener('click')) {
            cancelBtn.on('click', me.cancel.bind(me));
        }

        if (captureBtn !== null && !captureBtn.hasListener('click')) {
            captureBtn.on('click', me.capture.bind(me));
        }

        if (refundBtn !== null && !refundBtn.hasListener('click')) {
            refundBtn.on('click', me.refund.bind(me));
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
    }
});

//{/block}
