;(function ($) {
    'use strict';

    $.plugin('adyen-confirm-order', {
        /**
         * Plugin default options.
         */
        defaults: {
            checkoutConfigUrl: '',
            additionalDataUrl: '',
            checkoutShippingPaymentUrl: '/checkout/shippingPayment/sTarget/checkout',
            adyenPaymentMethodType: '',
            placeOrderSelector: '.table--actions button[type=submit]',
            confirmFormSelector: '#confirm--form',
            stateDataInputSelector: 'input[name=adyenPaymentMethodStateData]'
        },

        submitButtonReplacingComponents: ['applepay', 'amazonpay', 'paywithgoogle', 'googlepay', 'paypal'],
        checkoutController: null,

        init: function () {
            let me = this;

            me.applyDataAttributes();

            if (!me.submitButtonReplacingComponents.includes(me.opts.adyenPaymentMethodType)) {
                return;
            }

            me.checkoutController = new AdyenComponents.CheckoutController({
                "checkoutConfigUrl": me.opts.checkoutConfigUrl,
                "showPayButton": true,
                "sessionStorage": StorageManager.getStorage('session'),
                "onStateChange": $.proxy(me.submitOrder, me),
                "onAdditionalDetails": $.proxy(me.onAdditionalDetails, me),
                "onPayButtonClick": $.proxy(me.onPayButtonClick, me)
            });

            me.replacePlaceOrderButton();
        },

        replacePlaceOrderButton: function () {
            let me = this,
                orderButton = $(me.opts.placeOrderSelector);

            orderButton.parent().append(
                $('<div />')
                    .attr('data-adyen-submit-button', 'true')
                    .addClass('right')
            );
            orderButton.remove();

            me.checkoutController.mount(me.opts.adyenPaymentMethodType, '[data-adyen-submit-button]');
        },

        onPayButtonClick: function (resolve, reject) {
            let isValid = $(this.opts.confirmFormSelector)[0].checkValidity();

            isValid ? resolve() : reject('Form validation error.');

            return isValid;
        },

        submitOrder: function () {
            let me = this;

            if (!me.checkoutController.getPaymentMethodStateData()) {
                return;
            }

            if (!$(me.opts.confirmFormSelector)[0].checkValidity()) {
                return;
            }

            // Make sure that wallet payment state data is submitted
            $(me.opts.stateDataInputSelector).val(me.checkoutController.getPaymentMethodStateData());
            if (me.opts.adyenPaymentMethodType !== 'paypal') {
                $(me.opts.confirmFormSelector).submit();

                return;
            }

            var form = $(me.opts.confirmFormSelector);
            var url = form.attr('action');
            $.ajax({
                type: "POST",
                url: url+'/isXHR/1',
                data: form.serialize(),
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
        }
    });
})(jQuery);
