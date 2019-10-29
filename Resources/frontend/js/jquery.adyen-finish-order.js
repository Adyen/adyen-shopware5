;(function ($) {
    'use strict';

    $.plugin('adyen-finish-order', {
        /**
         * Plugin default options.
         */
        defaults: {
            placeOrderSelector: '.table--actions button[type=submit]',
            confirmFormSelector: '#confirm--form',
            mountRedirectSelector: '.is--act-confirm',
            ajaxDoPaymentUrl: '/frontend/adyen/ajaxDoPayment', // TODO refactor
            ajaxIdentifyShopperUrl: '/frontend/adyen/ajaxIdentifyShopper', // TODO refactor
            ajaxChallengeShopperUrl: '/frontend/adyen/ajaxChallengeShopper', // TODO refactor
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
                    'origin': window.location.origin
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

            $(me.opts.placeOrderSelector).parent().append('<div id="AdyenIdentifyShopperThreeDS2"/>');
            var threeDS2IdentifyShopper = me.adyenCheckout
                .create('threeDS2DeviceFingerprint', {
                    fingerprintToken: data.authentication['threeds2.fingerprintToken'],
                    onComplete: function(fingerprintData) {
                        $.ajax({
                            method: "POST",
                            dataType: 'json',
                            url: me.opts.ajaxIdentifyShopperUrl,
                            data: fingerprintData.data.details,
                            success: function (response) {
                                console.log('success', response);
                                me.handlePaymentData(response);
                            },
                        });
                    }, // Called whenever a result is available, regardless if the outcome is successful or not.
                    onError: function(error) {
                        console.error(error);
                    } // Gets triggered on error.
                })
                .mount('#AdyenIdentifyShopperThreeDS2');
        },

        handlePaymentDataChallengeShopper: function (data) {
            var me = this;

            var modal = $.modal.open('<div id="AdyenChallengeShopperThreeDS2"/>', {
                showCloseButton: false,
                closeOnOverlay: false,
                additionalClass: 'adyen-challenge-shopper'
            });
            var threeDS2Challenge = me.adyenCheckout
                .create('threeDS2Challenge', {
                    challengeToken: data.authentication['threeds2.challengeToken'],
                    onComplete: function(challengeData) {
                        modal.close();
                        $.ajax({
                            method: "POST",
                            dataType: 'json',
                            url: me.opts.ajaxChallengeShopperUrl,
                            data: challengeData.data.details,
                            success: function (response) {
                                me.handlePaymentData(response);
                            },
                        });
                    }, // Called whenever a result is available, regardless if the outcome is successful or not.
                    onError: function(error) {
                        console.log(error);
                    }, // Gets triggered on error.
                    size: '05' // Defaults to '01'
                })
                .mount('#AdyenChallengeShopperThreeDS2');
        },

        handlePaymentDataRedirectShopper: function (data) {
            var me = this;
            if (data.action.type === 'redirect') {
                me.adyenCheckout.createFromAction(data.action).mount(me.opts.mountRedirectSelector);
            }
        },

        handlePaymentDataError: function (data) {
            var me = this;
            var message = 'Your transaction was cancelled due to an unknown reason.';
            switch (data.resultCode) {
                case 'Cancelled':
                    message = 'Your transaction was cancelled by the Payment Service Provider.';
                    break;
                case 'Error':
                    message = 'An error occured while processing your payment. ' + data.refusalReason;
                    break;
                case 'Refused':
                    message = 'Your transaction was refused by the Payment Service Provider. ' + data.refusalReason;
                    break;
            }
            $.publish('plugin/MeteorAdyenCheckoutError/addError', message);
            $.publish('plugin/MeteorAdyenCheckoutError/scrollToErrors');

            me.$el.find(me.opts.placeOrderSelector)
                .removeAttr('disabled')
                .removeClass('disabled')
                .find('.js--loading')
                .remove();
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