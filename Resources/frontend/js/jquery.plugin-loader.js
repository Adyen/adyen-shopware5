;(function ($) {
    'use strict';

    $(function () {
        StateManager
            .addPlugin('[data-adyen-payment-selection]', 'adyen-payment-selection')
            .addPlugin('[data-adyen-confirm-order]', 'adyen-confirm-order')
            .addPlugin('[data-adyen-express-checkout]', 'adyen-express-checkout')
            .addPlugin('input[name=adyenPaymentMethodStateData]', 'adyen-payment-method-state-data-setter')
            .addPlugin('[data-adyen-payment-additional-action]', 'adyen-payment-additional-action')
            .addPlugin('#shipping_payment_wrapper', 'adyen-cookie-consent-visibility-handler')
            .addPlugin('body:not(.is--ctl-checkout)', 'adyen-state-data-cleanup')
            .addPlugin('.is--act-finish', 'adyen-state-data-cleanup')
            .addPlugin('[data-adyen-disable-payment]', 'adyen-disable-payment')
            .addPlugin('#donation-container', 'adyen-donations');

        $.subscribe('plugin/swAjaxVariant/onRequestData', function () {
            StateManager
                .addPlugin('[data-adyen-express-checkout]', 'adyen-express-checkout');
        });
    });
})(jQuery);
