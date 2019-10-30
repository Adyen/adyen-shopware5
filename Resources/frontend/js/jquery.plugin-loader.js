;(function ($) {
    'use strict';
    $(function () {
        StateManager
            .addPlugin('.is--act-confirm', 'adyen-finish-order')
            .addPlugin('.adyen-payment-selection', 'adyen-payment-selection')
            .addPlugin('*[data-adyen-checkout-error="true"]', 'adyen-checkout-error');
    });
})(jQuery);