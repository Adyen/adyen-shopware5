;(function ($) {
    'use strict';

    $.plugin('adyen-cookie-consent-visibility-handler', {
        /**
         * Plugin default options.
         */
        defaults: {
            paymentMethodBlockSelector: '.payment--method.block',
            adyenPaymentMethodSelector: '[data-adyen-payment-method]'
        },

        init: function () {
            let me = this;

            me.applyDataAttributes();

            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChanged'), $.proxy(me.handleAdyenPaymentVisibility, me));
            $.subscribe('plugin/swCookiePermission/onDeclineButtonClick', $.proxy(me.handleAdyenPaymentVisibility, me));

            me.handleAdyenPaymentVisibility();
        },

        handleAdyenPaymentVisibility: function() {
            let me = this;

            if (!window.StateManager.hasCookiesAllowed()) {
                me.hideAllAdyenPaymentMethods();
            }

            me.$el.removeClass('adyen-hidden--all');
        },

        hideAllAdyenPaymentMethods: function () {
            let me = this;

            me.$el.find(me.opts.adyenPaymentMethodSelector)
                .closest(me.opts.paymentMethodBlockSelector)
                .addClass('adyen-hidden--all');
        }
    });

})(jQuery);

