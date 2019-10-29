;(function ($) {
    'use strict';

    $(function () {
        StateManager.addPlugin('.is--act-confirm', 'adyen-finish-order');
    });


    $.plugin('adyen-finish-order', {
        /**
         * Plugin default options.
         */
        defaults: {
            placeOrderSelector: '.table--actions button[type=submit]',
            confirmFormSelector: '#confirm--form',
            mountRedirectSelector: '.is--act-confirm',
            ajaxDoPaymentUrl: '/frontend/adyen/ajaxDoPayment', // TODO refactor
        },
        adyenConfiguration: {},
        adyenCheckout: null,

        init: function () {
            var me = this;

            me.sessionStorage = StorageManager.getStorage('session');

            me.applyDataAttributes();
            me.eventListeners();
            me.setConfig();
            me.setCheckout();
        },

        eventListeners: function () {
            var me = this;

            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChangedBefore'), $.proxy(me.onPaymentChangedBefore, me));
            $.subscribe(me.getEventName('plugin/swShippingPayment/onInputChanged'), $.proxy(me.onPaymentChangedAfter, me));

            me._on(me.opts.placeOrderSelector, 'click', $.proxy(me.onPlaceOrder, me));
        },

        onPlaceOrder: function (event) {
            var me = this;

            event.preventDefault();

            if (me.sessionStorage.getItem('paymentMethod')) {

                var data = {
                    'paymentMethod': me.getPaymentMethod(),
                    'browserInfo': me.getBrowserInfo(),
                };

                $.ajax({
                    method: "POST",
                    dataType: 'json',
                    url: me.opts.ajaxDoPaymentUrl,
                    data: data,
                    success: function (response) {
                        me.handlePaymentData(response);
                    },
                });

            } else {
                $(me.opts.confirmFormSelector).submit();
            }
        },

        handlePaymentData: function (data) {
            var me = this;

            switch (data.resultCode) {
                case 'Authorised':
                    me.handlePaymentDataAuthorised(data);
                    break;
                case 'IdentifyShopper':
                    me.handlePaymentDataIdentifyShopper(data);
                    break;
                case 'ChallengeShopper':
                    me.handlePaymentDataChallengeShopper(data);
                    break;
                case 'RedirectShopper':
                    me.handlePaymentDataRedirectShopper(data);
                    break;
                default:
                    me.handlePaymentDataError(data);
                    break;
            }
        },

        handlePaymentDataAuthorised: function (data) {
            var me = this;
            $(me.opts.confirmFormSelector).submit();
        },

        handlePaymentDataIdentifyShopper: function (data) {
            var me = this;
            alert('Identify Shopper');
        },

        handlePaymentDataChallengeShopper: function (data) {
            var me = this;
            alert('Challenge Shopper');
        },

        handlePaymentDataRedirectShopper: function (data) {
            var me = this;
            if (data.action.type === 'redirect') {
                me.adyenCheckout.createFromAction(data.action).mount(me.opts.mountRedirectSelector);
            }
        },

        handlePaymentDataError: function (data) {
            var me = this;
            alert('Error! ' + JSON.stringify(data));
        },

        setConfig: function () {
            var me = this;

            var adyenConfig = me.getAdyenConfigSession();

            me.adyenConfiguration = {
                locale: adyenConfig.locale,
                environment: adyenConfig.environment,
                originKey: adyenConfig.originKey,
                paymentMethodsResponse: adyenConfig.paymentMethodsResponse,
                onAdditionalDetails: $.proxy(me.handleOnAdditionalDetails, me),
            };
        },

        setCheckout: function () {
            var me = this;

            me.adyenCheckout = new AdyenCheckout(me.adyenConfiguration);
        },

        getPaymentMethod: function () {
            var me = this;

            return me.sessionStorage.getItem('paymentMethod');
        },

        getAdyenConfigSession: function () {
            var me = this;

            return me.sessionStorage.getItem('adyenConfig');
        },

        getBrowserInfo: function () {
            return {
                'language': navigator.language,
                'userAgent': navigator.userAgent,
                'colorDepth': window.screen.colorDepth,
                'screenHeight': window.screen.height,
                'screenWidth': window.screen.width,
                'timeZoneOffset': new Date().getTimezoneOffset(),
                'javaEnabled': navigator.javaEnabled()
            };
        },

        handleOnAdditionalDetails: function (state, component) {
            //todo show popup
            console.log('got additional data', {state: state, component: component});
        },

    });

})(jQuery);