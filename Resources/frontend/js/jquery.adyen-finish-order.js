;(function ($) {
    'use strict';

    $(function () {
        //todo: attach to dom
        StateManager.addPlugin('', 'adyen-finish-order');
    });

    $.plugin('adyen-finish-order', {
        /**
         * Plugin default options.
         */
        defaults: {},
        init: function () {
        },
    });

})(jQuery);