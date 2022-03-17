;(function ($) {
    'use strict';
    $.plugin('adyen-disable-payment', {
        /**
         * Plugin default options.
         */
        defaults: {
            adyenDisableTokenUrl: '/frontend/disableRecurringToken/disabled',
            /**
             * Selector for the stored payment "disable" button.
             *
             * @type {String}
             */
            disableTokenSelector: '[data-adyen-disable-payment]',
            /**
             * @var string errorClass
             * CSS classes for the error element
             */
            errorClass: 'alert is--error is--rounded is--adyen-error',
            /**
             * @var string errorClassSelector
             * CSS classes selector to clear the error elements
             */
            errorClassSelector: '.alert.is--error.is--rounded.is--adyen-error',
            /**
             * @var string errorMessageClass
             * CSS classes for the error message element
             */
            errorMessageClass: 'alert--content'
        },
        init: function () {
            var me = this;
            me.$el.on('click', $.proxy(me.enableDisableButtonClick, me));
        },
        enableDisableButtonClick: function () {
            var me = this;
            if(0 === me.$el.data('adyenDisablePayment').length){
                return;
            }
            $.loadingIndicator.open();
            $.post({
                url: me.opts.adyenDisableTokenUrl,
                dataType: 'json',
                data: {recurringToken: me.$el.data('adyenDisablePayment')},
                success: function () {
                    window.location.reload();
                }
            }).fail(function(response) {
                me.appendError(response.responseJSON.message);
            }).always(function() {
                $.loadingIndicator.close();
            });
        },
        appendError: function (message) {
            var me = this;
            $(me.opts.errorClassSelector).remove();
            var error = $('<div />').addClass(me.opts.errorClass);
            error.append($('<div />').addClass(me.opts.errorMessageClass).html(message));
            me.$el.parent().append(error);
        }
    });
})(jQuery);
