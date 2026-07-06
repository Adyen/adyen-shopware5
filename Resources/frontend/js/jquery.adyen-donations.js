;(function ($) {
    'use strict';

    $.plugin('adyen-donations', {
        /**
         * Plugin default options.
         */
        defaults: {
            donationsConfigUrl: '',
            makeDonationsUrl: '',
            countryCode: ''
        },

        donationsController : null,

        init: function () {
            let me = this;

            me.applyDataAttributes();

            me.donationsController = new AdyenComponents.DonationsController({
                "donationsConfigUrl": me.opts.donationsConfigUrl,
                "countryCode": me.opts.countryCode,
                "makeDonation": $.proxy(me.makeDonation, me)
            });

            me.donationsController.mount(me.$el[0]);
        },

        makeDonation: function (data) {
            let me = this;

            $.ajax({
                method: 'POST',
                dataType: 'json',
                url: me.opts.makeDonationsUrl,
                data: data,
                success: function () {
                    window.location.reload();
                },
                error: function () {
                    me.donationsController.unmount();
                    window.location.reload();
                }
            });
        }
    });
})(jQuery);
