;(function ($) {
    'use strict';

    $.plugin('adyen-express-checkout', {
        /**
         * Plugin default options.
         */
        defaults: {
            checkoutConfigUrl: '',
            additionalDataUrl: '',
            paypalUpdateOrder: '',
            checkoutShippingPaymentUrl: '/checkout/shippingPayment/sTarget/checkout',
            adyenPaymentMethodType: '',
            stateDataInputSelector: 'input[name=adyenExpressPaymentMethodStateData]',
            shippingAddressInputSelector: 'input[name=adyenShippingAddress]',
            billingAddressInputSelector: 'input[name=adyenBillingAddress]',
            shippingAddress: [],
            billingAddress: [],
            emailInputSelector: 'input[name=adyenEmail]',
            confirmFormSelector: 'form[data-adyen-express-checkout-form]',
            userLoggedIn: $('input[name=adyenLoggedIn]').length
        },

        checkoutController: null,

        init: function () {
            let me = this;

            me.applyDataAttributes();

            me.checkoutController = new AdyenComponents.CheckoutController({
                "checkoutConfigUrl": me.opts.checkoutConfigUrl,
                "showPayButton": true,
                "requireAddress": !me.opts.userLoggedIn,
                "requireEmail": !me.opts.userLoggedIn,
                "sessionStorage": StorageManager.getStorage('session'),
                "onStateChange": $.proxy(me.submitOrder, me),
                "onAdditionalDetails": $.proxy(me.onAdditionalDetails, me),
                "onAuthorized": $.proxy(me.onAuthorized, me),
                "onPaymentAuthorized": $.proxy(me.onPaymentAuthorized, me),
                "onPaymentDataChanged": $.proxy(me.onPaymentDataChanged, me),
                "onApplePayPaymentAuthorized": $.proxy(me.onApplePayPaymentAuthorized, me),
                "onShippingContactSelected": $.proxy(me.onShippingContactSelected, me),
                "onShopperDetails": $.proxy(me.onShopperDetails, me),
                "onShippingAddressChanged": $.proxy(me.onShippingAddressChanged, me),
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

            if (me.opts.adyenPaymentMethodType === 'applepay' && !me.opts.userLoggedIn) {
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
                url: url + '/isXHR/1',
                data: expressCheckoutForm.serialize(),
                success: function (data) {
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

                    if (data.pspReference) {
                        me.pspReference = data.pspReference;
                    }

                    me.paymentData = null;
                    if (data.action.paymentData) {
                        me.paymentData = data.action.paymentData
                    }

                    me.checkoutController.handleAction(data.action);
                },
                error: function (data) {
                    window.location.href = me.opts.checkoutShippingPaymentUrl;
                }
            });
        },

        onAdditionalDetails: function (additionalData) {
            let me = this;

            if (me.paymentData) {
                additionalData.paymentData = me.paymentData

                if (additionalData.details.paymentSource === 'paypal') {
                    let expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);

                    additionalData.adyenShippingAddress = expressCheckoutForm.find(me.opts.shippingAddressInputSelector).val();
                    additionalData.adyenBillingAddress = expressCheckoutForm.find(me.opts.billingAddressInputSelector).val();
                    additionalData.adyenEmail = expressCheckoutForm.find(me.opts.emailInputSelector).val();
                }
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
            console.log('Shopper details', paymentData);
        },

        onPaymentAuthorized: function (paymentData) {
            let me = this;

            return new Promise(function (resolve, reject) {
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
                const {shippingAddress} = intermediatePaymentData;
                const paymentDataRequestUpdate = {};
                let amount = 0,
                    expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);

                me.opts.shippingAddress = {
                    zipCode: shippingAddress.postalCode,
                    city: shippingAddress.locality,
                    country: shippingAddress.countryCode,
                };
                expressCheckoutForm.find(me.opts.shippingAddressInputSelector).val(JSON.stringify(me.opts.shippingAddress));

                var url = me.opts.checkoutConfigUrl;
                $.ajax({
                    type: "POST",
                    url: url + '/isXHR/1',
                    data: expressCheckoutForm.serialize(),
                    success: function (response) {
                        amount = parseInt(response.amount) / 100;

                        paymentDataRequestUpdate.newTransactionInfo = {
                            currencyCode: response.currency,
                            totalPriceStatus: "FINAL",
                            totalPrice: (amount).toString(),
                            totalPriceLabel: "Total",
                            countryCode: response.country,
                        };
                        resolve(paymentDataRequestUpdate);
                    },
                    error: function (response) {
                        paymentDataRequestUpdate.error = {
                            reason: "SHIPPING_ADDRESS_UNSERVICEABLE",
                            message: response.responseJSON.message ?? "Cannot ship to the selected address",
                            intent: "SHIPPING_ADDRESS"
                        };
                        resolve(paymentDataRequestUpdate);
                    }
                });
            });
        },

        onApplePayPaymentAuthorized: function (resolve, reject, event) {
            let me = this;

            let expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);
            let shippingContact = event.payment.shippingContact;
            me.opts.shippingAddress = {
                firstName: shippingContact.givenName,
                lastName: shippingContact.familyName,
                street: shippingContact.addressLines.length > 0 ? shippingContact.addressLines[0] : '',
                city: shippingContact.locality,
                country: shippingContact.countryCode,
                zipCode: shippingContact.postalCode,
                phone: shippingContact.phoneNumber,
            };

            expressCheckoutForm.find(me.opts.shippingAddressInputSelector).val(JSON.stringify(me.opts.shippingAddress));
            expressCheckoutForm.find(me.opts.billingAddressInputSelector).val(JSON.stringify(me.opts.shippingAddress));
            expressCheckoutForm.find(me.opts.emailInputSelector).val(JSON.stringify(shippingContact.emailAddress));

            if (!me.checkoutController.getPaymentMethodStateData()) {
                return;
            }

            expressCheckoutForm.find(me.opts.stateDataInputSelector).val(me.checkoutController.getPaymentMethodStateData());
            var url = expressCheckoutForm.attr('action');
            $.ajax({
                type: "POST",
                url: url + '/isXHR/1',
                data: expressCheckoutForm.serialize(),
                success: function (response) {
                    if (response.nextStepUrl.includes('checkout/finish')) {
                        resolve(window.ApplePaySession.STATUS_SUCCESS);
                        window.location.href = response.nextStepUrl;
                    } else {
                        reject(window.ApplePaySession.STATUS_FAILURE);
                        window.location.href = response.nextStepUrl;
                    }
                },
                error: function () {
                    reject(window.ApplePaySession.STATUS_FAILURE);
                    window.location.reload();
                }
            });
        },

        onShippingContactSelected: function (resolve, reject, event) {
            let me = this;

            let expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);
            let address = event.shippingContact;
            let amount = 0;

            me.opts.shippingAddress = {
                city: address.locality,
                country: address.countryCode,
                zipCode: address.postalCode,
            };

            expressCheckoutForm.find(me.opts.shippingAddressInputSelector).val(JSON.stringify(me.opts.shippingAddress));
            expressCheckoutForm.find(me.opts.billingAddressInputSelector).val(JSON.stringify(me.opts.shippingAddress));

            var url = me.opts.checkoutConfigUrl;
            $.ajax({
                type: "POST",
                url: url + '/isXHR/1',
                data: expressCheckoutForm.serialize(),
                success: function (response) {
                    amount = parseInt(response.amount) / 100;
                    let applePayShippingMethodUpdate = {};

                    applePayShippingMethodUpdate.newTotal = {
                        type: 'final',
                        label: 'Total amount',
                        amount: (amount).toString()
                    };

                    resolve(applePayShippingMethodUpdate);
                },
                error: function (response) {
                    let update = {
                        newTotal: {
                            type: 'final',
                            label: 'Total amount',
                            amount: (amount).toString()
                        },
                        errors: [new ApplePayError(
                            'shippingContactInvalid',
                            'countryCode',
                            response.responseJSON.message ?? 'Error')
                        ]
                    };
                    resolve(update);
                }
            });
        },

        onShopperDetails: function (shopperDetails, rawData, actions) {
            let me = this,
                expressCheckoutForm = me.$el.closest(me.opts.confirmFormSelector);

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
        },

        onShippingAddressChanged: function (data, actions, component) {
            let me = this;
            var url = me.opts.paypalUpdateOrder;

            $.ajax({
                type: "POST",
                url: url + '/isXHR/1',
                data: {
                    shippingAddress: data.shippingAddress,
                    paymentData: component.paymentData,
                    pspReference: me.pspReference
                },
                success: function (response) {
                    component.updatePaymentData(response.paymentData);
                    actions.resolve();
                },
                error: function (response) {
                    actions.reject(new Error('fail'));
                }
            });
        }
    });
})(jQuery);
