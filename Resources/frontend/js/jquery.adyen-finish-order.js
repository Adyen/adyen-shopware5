;(function ($) {
    'use strict';

    $(function () {
        StateManager.addPlugin('.is--act-confirm', 'adyen-finish-order');
    });


    $.plugin('adyen-finish-order', {
        /**
         * Plugin default options.
         */
        defaults: {},
        init: function () {
            var me = this;


            me.eventListeners();
        },
        eventListeners: function () {
            var me = this;

            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChangedBefore'), $.proxy(me.onPaymentChangedBefore, me));
            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChanged'), $.proxy(me.onPaymentChangedAfter, me));
        },

    });

})(jQuery);