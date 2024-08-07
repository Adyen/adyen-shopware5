;(function ($) {
    'use strict';

    $.plugin('adyen-express-checkout', {
        /**
         * Plugin default options.
         */
        defaults: {
            checkoutConfigUrl: '',
            additionalDataUrl: '',
            checkoutShippingPaymentUrl: '/checkout/shippingPayment/sTarget/checkout',
            adyenPaymentMethodType: '',
            stateDataInputSelector: 'input[name=adyenExpressPaymentMethodStateData]',
            shippingAddressInputSelector: 'input[name=adyenShippingAddress]',
            confirmFormSelector: 'form[data-adyen-express-checkout-form]',
        },

        checkoutController: null,

        init: function () {
            let me = this;

            me.applyDataAttributes();

            me.checkoutController = new AdyenComponents.CheckoutController({
                "checkoutConfigUrl": me.opts.checkoutConfigUrl,
                "showPayButton": true,
                "sessionStorage": StorageManager.getStorage('session'),
                "onStateChange": $.proxy(me.submitOrder, me),
                "onAdditionalDetails": $.proxy(me.onAdditionalDetails, me),
                "onPaymentAuthorized": $.proxy(me.onPaymentAuthorized, me),
                "onPaymentDataChanged": $.proxy(me.onPaymentDataChanged, me),
                "onShippingAddressChange": $.proxy(me.onShippingAddressChange, me),
                "onShopperDetails": $.proxy(me.onShopperDetails, me),
            });

            me.mountExpressCheckoutButtons();
        },

        mountExpressCheckoutButtons: function () {
            let me = this;

            me.checkoutController.mount(me.opts.adyenPaymentMethodType, me.$el[0]);
        },

        submitOrder: function () {
            let me = this;

            if (!me.checkoutController.getPaymentMethodStateData()) {
                me.checkoutController.forceFetchingComponentStateData();

                return;
            }

            let expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);

            // Make sure that wallet payment state data is submitted
            expressCheckoutForm.find(me.opts.stateDataInputSelector).val(me.checkoutController.getPaymentMethodStateData());
            if (me.opts.adyenPaymentMethodType !== 'paypal') {
                expressCheckoutForm.submit();

                return;
            }

            var url = expressCheckoutForm.attr('action');
            $.ajax({
                type: "POST",
                url: url+'/isXHR/1',
                data: expressCheckoutForm.serialize(),
                success: function(data) {
                    if (data.nextStepUrl) {
                        window.location.href = data.nextStepUrl;
                        return;
                    }

                    if (!data.action) {
                        window.location.href = me.opts.checkoutShippingPaymentUrl;
                        return;
                    }

                    me.signature = data.signature;
                    me.reference = data.reference;
                    me.paymentData = null;
                    if (data.action.paymentData) {
                        me.paymentData = data.action.paymentData
                    }

                    me.checkoutController.handleAction(data.action);
                },
                error: function(data) {
                    window.location.href = me.opts.checkoutShippingPaymentUrl;
                }
            });
        },

        onAdditionalDetails: function (additionalData) {
            let me = this;

            if (me.paymentData) {
                additionalData.paymentData = me.paymentData
            }
            $.ajax({
                method: 'POST',
                dataType: 'json',
                url: me.opts.additionalDataUrl + "/signature/" + me.signature + "/reference/" + me.reference + '/isXHR/1',
                data: additionalData,
                success: function (response) {
                    window.location.href = response.nextStepUrl;
                },
                error: function () {
                    window.location.href = me.opts.checkoutShippingPaymentUrl;
                }
            });
        },

        onPaymentAuthorized: function (paymentData) {
            return new Promise(function(resolve, reject){
                console.log(paymentData);
            });
        },

        onPaymentDataChanged: function (intermediatePaymentData) {
            return new Promise(async resolve => {
                const { callbackTrigger, shippingAddress } = intermediatePaymentData;
                const paymentDataRequestUpdate = {};

                let me = this;
                let expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);
                expressCheckoutForm.find(me.opts.shippingAddressInputSelector).val(JSON.stringify(shippingAddress));

                resolve(paymentDataRequestUpdate);
            });
        },

        onShippingAddressChange: function (data, actions, component) {
            const currentPaymentData = component.paymentData;
            console.log(currentPaymentData);
        },

        onShopperDetails: function (shopperDetails, rawData, actions) {
            actions.resolve();
            console.log(shopperDetails);
        }
    });
})(jQuery);
