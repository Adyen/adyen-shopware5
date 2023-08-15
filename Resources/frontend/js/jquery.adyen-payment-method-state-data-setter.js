;(function ($) {
    'use strict';

    $.plugin('adyen-payment-method-state-data-setter', {
        /**
         * Plugin default options.
         */
        defaults: {
            checkoutConfigUrl: '',
            checkoutShippingPaymentUrl: '/checkout/shippingPayment/sTarget/checkout'
        },

        init: function () {
            let me = this;

            me.applyDataAttributes();

            let checkoutController = new AdyenComponents.CheckoutController({
                "checkoutConfigUrl": me.opts.checkoutConfigUrl,
                "sessionStorage": StorageManager.getStorage('session')
            });

            if (checkoutController.isPaymentMethodStateReinitializationRequired()) {
                window.location.href = me.opts.checkoutShippingPaymentUrl;
            }

            if (checkoutController.getPaymentMethodStateData()) {
                me.$el.val(checkoutController.getPaymentMethodStateData());
            }
        }
    });

})(jQuery);

