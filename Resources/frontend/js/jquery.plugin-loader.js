;(function ($) {
    'use strict';
    $(function () {
        StateManager
            .addPlugin('.adyen-payment-selection', 'adyen-payment-selection')
            .addPlugin('*[data-adyen-checkout-error="true"]', 'adyen-checkout-error')
            .addPlugin('.is--act-confirm', 'adyen-confirm-order')
            .addPlugin('.is--act-finish', 'adyen-finish-order');
    });
})(jQuery);
