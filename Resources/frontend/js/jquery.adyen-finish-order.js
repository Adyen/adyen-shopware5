;(function ($) {
    'use strict';

    $.plugin('adyen-finish-order', {
        sessions: [
            'adyenConfig',
            'paymentMethod'
        ],

        init: function () {
            var me = this;
            me.sessionStorage = StorageManager.getStorage('session');
            me.cleanupSessions();
        },

        cleanupSessions: function () {
            var me = this;
            me.sessions.forEach(function (session) {
                me.sessionStorage.removeItem(session);
            });
        }
    });
})(jQuery);
