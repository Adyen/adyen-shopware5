;(function ($) {
    'use strict';

    /**
     * Plugin to show errors in the Shopware Checkout using javascript 'simple pub/sub' events.
     * Initialise using the StateManager
     *
     * -- Adding an error message
     * $.publish('plugin/MeteorAdyenCheckoutError/addError', 'Something went wrong');
     *
     * -- Clearing all Adyen error messages
     * $.publish('plugin/MeteorAdyenCheckoutError/cleanErrors');
     */
    $.plugin('adyen-checkout-error', {
        defaults: {
            /**
             * @var string errorClass
             * CSS classes for the error element
             */
            errorClass: 'alert is--error is--rounded is--adyen-error',

            /**
             * @var string errorMessageClass
             * CSS classes for the error message element
             */
            errorMessageClass: 'alert--content',

            /**
             * @var bool showIcon
             * Whether to show or not show the icon
             */
            showIcon: true,

            /**
             * @var string errorMessageClass
             * The icon to show. Defaults to a cross
             */
            showIconIcon: 'icon--cross'
        },

        init: function () {
            var me = this;

            me.applyDataAttributes();
            me.eventListeners();
        },

        /**
         * Initialise event listeners for error handling
         */
        eventListeners: function() {
            var me = this;
            $.subscribe(me.getEventName('plugin/MeteorAdyenCheckoutError/addError'), $.proxy(me.onAddError, me));
            $.subscribe(me.getEventName('plugin/MeteorAdyenCheckoutError/cleanErrors'), $.proxy(me.onCleanErrors, me));
            $.subscribe(me.getEventName('plugin/MeteorAdyenCheckoutError/scrollToErrors'), $.proxy(me.onScrollTo, me));
        },

        /**
         * Add errors to the element
         *
         * @param o To be ignored
         * @param message The error message
         */
        onAddError: function (o, message) {
            var me = this;
            me.$el.append(me.createError(message));
        },

        /**
         * Removes all errors from the element
         */
        onCleanErrors: function () {
            var me = this;
            me.$el.children().remove();
        },

        onScrollTo: function() {
            var me = this;
            window.scroll(0, me.$el.offset().top - (window.innerHeight/2));
        },

        /**
         * Create a Error message jQuery element
         *
         * @param message
         * @returns {jQuery}
         */
        createError: function (message) {
            var me = this;

            var error = $('<div />')
                .addClass(me.opts.errorClass);
            error.append(
                $('<div />')
                    .addClass(me.opts.errorMessageClass)
                    .html(message)
            );

            if (me.opts.showIcon) {
                var icon = $('<div />')
                    .addClass('alert--icon')
                    .append(
                        $('<i />')
                            .addClass('icon--element')
                            .addClass(me.opts.showIconIcon)
                    );

                error.prepend(icon);
            }

            return error;
        },
    });
})(jQuery);