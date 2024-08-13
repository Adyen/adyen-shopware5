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
            billingAddressInputSelector: 'input[name=adyenBillingAddress]',
            shippingAddress: [],
            billingAddress: [],
            emailInputSelector: 'input[name=adyenEmail]',
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
                "onAuthorized": $.proxy(me.onAuthorized, me),
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

        onAuthorized: function (paymentData) {
        },

        onPaymentAuthorized: function (paymentData) {
            let me = this;

            return new Promise(function(resolve, reject){
                let expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);

                me.opts.shippingAddress = {
                    firstName: paymentData.shippingAddress.name,
                    lastName: '',
                    street: paymentData.shippingAddress.address1,
                    zipCode: paymentData.shippingAddress.postalCode,
                    city: paymentData.shippingAddress.locality,
                    country: paymentData.shippingAddress.countryCode,
                    phone: paymentData.shippingAddress.phoneNumber
                };
                expressCheckoutForm.find(me.opts.shippingAddressInputSelector).val(JSON.stringify(me.opts.shippingAddress));
                expressCheckoutForm.find(me.opts.billingAddressInputSelector).val(JSON.stringify(me.opts.shippingAddress));
                expressCheckoutForm.find(me.opts.emailInputSelector).val(JSON.stringify(paymentData.email));

                resolve({transactionState: 'SUCCESS'});
            });
        },

        onPaymentDataChanged: function (intermediatePaymentData) {
            let me = this;

            return new Promise(async resolve => {
                const { callbackTrigger, shippingAddress } = intermediatePaymentData;
                const paymentDataRequestUpdate = {};

                let expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);
                expressCheckoutForm.find(me.opts.shippingAddressInputSelector).val(JSON.stringify(shippingAddress));

                resolve(paymentDataRequestUpdate);
            });
        },

        onShippingAddressChange: function (data, actions, component) {
        },

        onShopperDetails: function (shopperDetails, rawData, actions) {
            let expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);

            me.opts.shippingAddress = {
                firstName: shopperDetails.shopperName.firstName,
                lastName: shopperDetails.shopperName.lastName,
                street: shopperDetails.shippingAddress.street,
                zipCode: shopperDetails.shippingAddress.postalCode,
                city: shopperDetails.shippingAddress.city,
                country: shopperDetails.shippingAddress.country,
                phone: shopperDetails.telephoneNumber
            };
            me.opts.billingAddress = {
                firstName: shopperDetails.shopperName.firstName,
                lastName: shopperDetails.shopperName.lastName,
                street: shopperDetails.billingAddress.street,
                zipCode: shopperDetails.billingAddress.postalCode,
                city: shopperDetails.billingAddress.city,
                country: shopperDetails.billingAddress.country,
                phone: shopperDetails.telephoneNumber
            };

            expressCheckoutForm.find(me.opts.shippingAddressInputSelector).val(JSON.stringify(me.opts.shippingAddress));
            expressCheckoutForm.find(me.opts.billingAddressInputSelector).val(JSON.stringify(me.opts.billingAddress));
            expressCheckoutForm.find(me.opts.emailInputSelector).val(JSON.stringify(shopperDetails.shopperEmail));

            actions.resolve();
        }
    });
})(jQuery);
