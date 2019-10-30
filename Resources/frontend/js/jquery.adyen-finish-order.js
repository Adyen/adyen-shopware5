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
            AdyenAjaxDoPaymentUrl: '/frontend/adyen/ajaxDoPayment',
            AdyenAjaxIdentifyShopperUrl: '/frontend/adyen/ajaxIdentifyShopper',
            AdyenAjaxChallengeShopperUrl: '/frontend/adyen/ajaxChallengeShopper',
            AdyenSnippets: {
                errorTransactionCancelled: 'Your transaction was cancelled by the Payment Service Provider.',
                errorTransactionProcessing: 'An error occured while processing your payment.',
                errorTransactionRefused: 'Your transaction was refused by the Payment Service Provider.',
                errorTransactionUnknown: 'Your transaction was cancelled due to an unknown reason.',
            },
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
            me.clearAdyenError();

            if (me.sessionStorage.getItem('paymentMethod')) {
                if(!$(me.opts.confirmFormSelector)[0].checkValidity()) {
                    return;
                }
                
                $.loadingIndicator.open();

                var data = {
                    'paymentMethod': me.getPaymentMethod(),
                    'browserInfo': me.getBrowserInfo(),
                    'origin': window.location.origin
                };

                $.ajax({
                    method: "POST",
                    dataType: 'json',
                    url: me.opts.AdyenAjaxDoPaymentUrl,
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

            console.log(data);

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
            me.adyenCheckout
                .create('threeDS2DeviceFingerprint', {
                    fingerprintToken: data.authentication['threeds2.fingerprintToken'],
                    onComplete: function(fingerprintData) {
                        $.ajax({
                            method: "POST",
                            dataType: 'json',
                            url: me.opts.AdyenAjaxIdentifyShopperUrl,
                            data: fingerprintData.data.details,
                            success: function (response) {
                                me.handlePaymentData(response);
                            },
                        });
                    },
                    onError: function(error) {
                        console.error(error);
                    }
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
            me.adyenCheckout
                .create('threeDS2Challenge', {
                    challengeToken: data.authentication['threeds2.challengeToken'],
                    onComplete: function(challengeData) {
                        modal.close();
                        $.ajax({
                            method: "POST",
                            dataType: 'json',
                            url: me.opts.AdyenAjaxChallengeShopperUrl,
                            data: challengeData.data.details,
                            success: function (response) {
                                me.handlePaymentData(response);
                            },
                        });
                    },
                    onError: function(error) {
                        console.log(error);
                    },
                    size: '05'
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
            switch (data.resultCode) {
                case 'Cancelled':
                    this.addAdyenError(me.opts.AdyenSnippets.errorTransactionCancelled);
                    break;
                case 'Error':
                    this.addAdyenError(me.opts.AdyenSnippets.errorTransactionProcessing);
                    break;
                case 'Refused':
                    this.addAdyenError(me.opts.AdyenSnippets.errorTransactionRefused);
                    break;
                default:
                    this.addAdyenError(me.opts.AdyenSnippets.errorTransactionUnknown);
                    break;
            }
        },

        addAdyenError: function (message) {
            var me = this;
            $.publish('plugin/MeteorAdyenCheckoutError/addError', message);
            $.publish('plugin/MeteorAdyenCheckoutError/scrollToErrors');

            $(me.opts.placeOrderSelector)
                .removeAttr('disabled')
                .removeClass('disabled')
                .find('.js--loading')
                .remove();
        },

        clearAdyenError: function() {
            $.publish('plugin/MeteorAdyenCheckoutError/cleanErrors');
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
            $.loadingIndicator.close();
            console.log('got additional data', {state: state, component: component});
        },

    });

})(jQuery);