;(function ($) {
    'use strict';

    $.plugin('adyen-confirm-order', {
        /**
         * Plugin default options.
         */
        defaults: {
            placeOrderSelector: '.table--actions button[type=submit]',
            confirmFormSelector: '#confirm--form',
            mountRedirectSelector: '.is--act-confirm',
            adyenType: '',
            adyenGoogleConfig: {},
            adyenSetSession: {},
            adyenAjaxDoPaymentUrl: '/frontend/adyen/ajaxDoPayment',
            adyenAjaxIdentifyShopperUrl: '/frontend/adyen/ajaxIdentifyShopper',
            adyenAjaxChallengeShopperUrl: '/frontend/adyen/ajaxChallengeShopper',
            adyenSnippets: {
                errorTransactionCancelled: 'Your transaction was cancelled by the Payment Service Provider.',
                errorTransactionProcessing: 'An error occured while processing your payment.',
                errorTransactionRefused: 'Your transaction was refused by the Payment Service Provider.',
                errorTransactionUnknown: 'Your transaction was cancelled due to an unknown reason.',
                errorTransactionNoSession: 'Your transaction was cancelled due to an unknown reason. Please make sure your browser allows cookies.',
                errorGooglePayNotAvailable: 'Google Pay is currently not available.',
            },
        },
        paymentMethodSession: 'paymentMethod',
        adyenConfiguration: {},
        adyenCheckout: null,

        init: function () {
            var me = this;

            me.sessionStorage = StorageManager.getStorage('session');

            me.applyDataAttributes();
            me.eventListeners();
            me.checkSetSession();
            me.setConfig();
            me.setCheckout();
            me.handleCheckoutButton();
        },

        eventListeners: function () {
            var me = this;

            me._on(me.opts.placeOrderSelector, 'click', $.proxy(me.onPlaceOrder, me));
        },

        checkSetSession: function () {
            var me = this;
            if (!$.isEmptyObject(me.opts.adyenSetSession)) {
                me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(me.opts.adyenSetSession));
            }
        },

        onPlaceOrder: function (event) {
            var me = this;

            if (typeof event !== 'undefined') {
                event.preventDefault();
            }

            me.clearAdyenError();

            if (me.sessionStorage.getItem(me.paymentMethodSession)) {
                if (!$(me.opts.confirmFormSelector)[0].checkValidity()) {
                    return;
                }

                $.loadingIndicator.open();

                var data = {
                    'paymentMethod': me.getPaymentMethod(),
                    'browserInfo': me.getBrowserInfo(),
                    'origin': window.location.origin
                };

                $.ajax({
                    method: 'POST',
                    dataType: 'json',
                    url: me.opts.adyenAjaxDoPaymentUrl,
                    data: data,
                    success: function (response) {
                        if (response['status'] === 'success') {
                            me.handlePaymentData(response['content']);
                        } else {
                            me.addAdyenError(response['content']);
                        }

                        $.loadingIndicator.close();
                    },
                });
            } else {
                if ($('body').data('adyenisadyenpayment')) {
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionNoSession);
                    return;
                }

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
            me.adyenCheckout
                .create('threeDS2DeviceFingerprint', {
                    fingerprintToken: data.authentication['threeds2.fingerprintToken'],
                    onComplete: function (fingerprintData) {
                        $.ajax({
                            method: 'POST',
                            dataType: 'json',
                            url: me.opts.adyenAjaxIdentifyShopperUrl,
                            data: fingerprintData.data.details,
                            success: function (response) {
                                me.handlePaymentData(response);
                            },
                        });
                    },
                    onError: function (error) {
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
                    onComplete: function (challengeData) {
                        modal.close();
                        $.ajax({
                            method: 'POST',
                            dataType: 'json',
                            url: me.opts.adyenAjaxChallengeShopperUrl,
                            data: challengeData.data.details,
                            success: function (response) {
                                me.handlePaymentData(response);
                            },
                        });
                    },
                    onError: function (error) {
                        console.log(error);
                    }
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

            $.loadingIndicator.close();

            switch (data.resultCode) {
                case 'Cancelled':
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionCancelled);
                    break;
                case 'Error':
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionProcessing);
                    break;
                case 'Refused':
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionRefused);
                    break;
                default:
                    this.addAdyenError(me.opts.adyenSnippets.errorTransactionUnknown);
                    break;
            }
        },
        handleCheckoutButton: function () {
            var me = this;

            if (me.opts.adyenType === 'paywithgoogle') {
                me.replaceCheckoutButtonForGooglePay();
            }
        },

        replaceCheckoutButtonForGooglePay: function () {
            var me = this;

            var orderButton = $(me.opts.placeOrderSelector);
            orderButton.parent().append(
                $('<div />')
                    .attr('id', 'AdyenGooglePayButton')
                    .addClass('right')
            );
            orderButton.remove();

            me.opts.adyenGoogleConfig.onSubmit = function (state, component) {
                me.sessionStorage.setItem(me.paymentMethodSession, JSON.stringify(state.data.paymentMethod));
                me.onPlaceOrder();
            };

            var googlepay = me.adyenCheckout.create("paywithgoogle", me.opts.adyenGoogleConfig);
            googlepay
                .isAvailable()
                .then(function () {
                    googlepay.mount("#AdyenGooglePayButton");
                })
                .catch(function (e) {
                    this.addAdyenError(me.opts.adyenSnippets.errorGooglePayNotAvailable);
                });
        },
        addAdyenError: function (message) {
            var me = this;
            $.publish('plugin/AdyenPaymentCheckoutError/addError', message);
            $.publish('plugin/AdyenPaymentCheckoutError/scrollToErrors');

            $(me.opts.placeOrderSelector)
                .removeAttr('disabled')
                .removeClass('disabled')
                .find('.js--loading')
                .remove();
        },

        clearAdyenError: function () {
            $.publish('plugin/AdyenPaymentCheckoutError/cleanErrors');
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

            return me.sessionStorage.getItem(me.paymentMethodSession);
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
            $.loadingIndicator.close();
        },

    });
})(jQuery);
