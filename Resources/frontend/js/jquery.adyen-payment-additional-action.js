;(function ($) {
    'use strict';

    $.plugin('adyen-payment-additional-action', {
        /**
         * Plugin default options.
         */
        defaults: {
            checkoutConfigUrl: '',
            additionalDataUrl: '',
            additionalActionSelector: '#adyen-additional-action',
            checkoutShippingPaymentUrl: '/checkout/shippingPayment/sTarget/checkout',
            skipRedirect: ''
        },

        init: function () {
            let me = this;

            me.applyDataAttributes();

            if ($(me.opts.additionalActionSelector).html() === '' ||
                $(me.opts.additionalActionSelector).html() === 'undefined') {
                return;
            }

            const additionalAction = JSON.parse($(me.opts.additionalActionSelector).html());
            if (!additionalAction || !additionalAction.type) {
                window.location.href = me.opts.checkoutShippingPaymentUrl;
            }

            let checkoutController = new AdyenComponents.CheckoutController({
                "checkoutConfigUrl": me.opts.checkoutConfigUrl,
                "onAdditionalDetails": $.proxy(me.onAdditionalDetails, me),
                "sessionStorage": StorageManager.getStorage('session')
            });

            checkoutController.handleAdditionalAction(additionalAction, me.$el[0]);
        },

        onAdditionalDetails: function (additionalData) {
            let me = this;
            $.ajax({
                method: 'POST',
                dataType: 'json',
                url: me.opts.additionalDataUrl + '/isXHR/1',
                data: additionalData,
                success: function (response) {
                    if (me.opts.skipRedirect) {
                        return;
                    }

                    window.location.href = response.nextStepUrl;
                },
                error: function () {
                    window.location.href = me.opts.checkoutShippingPaymentUrl;
                }
            });
        }
    });

})(jQuery);

