;(function ($) {
    'use strict';

    $.plugin('adyen-state-data-cleanup', {
        /**
         * Plugin default options.
         */
        defaults: {
            checkoutConfigUrl: '',
        },

        init: function () {
            let me = this;

            me.applyDataAttributes();

            let checkoutController = new AdyenComponents.CheckoutController({
                "checkoutConfigUrl": me.opts.checkoutConfigUrl,
                "sessionStorage": StorageManager.getStorage('session')
            });

            checkoutController.unmount();
        }
    });

})(jQuery);

